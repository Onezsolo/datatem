<?php

namespace App\Services;

use App\Models\AffiliateWithdrawal;
use App\Models\ResellerWithdrawal;
use App\Models\Purchase;
use App\Models\ResellerRequest;
use App\Models\Topup;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Support\GhanaPhone;

/**
 * Send in-app notifications, optional email, and SMS for purchase/topup status.
 */
final class NotifyService
{
    public static function emailBody(string $title, string $message, ?string $url = null): string
    {
        $html = '<p><strong>' . e($title) . '</strong></p><p>' . nl2br(e($message)) . '</p>';
        if ($url) {
            $html .= '<p><a href="' . e($url) . '" style="color:#2563eb;">View details</a></p>';
        }
        return $html;
    }

    /**
     * Send email to all admins that have an email address, and to the configured admin notification email (e.g. business inbox).
     */
    public static function sendAdminEmails(string $subject, string $bodyHtml): void
    {
        if (! EmailService::isConfigured()) {
            return;
        }
        $admins = User::where('role', User::ROLE_ADMIN)->get();
        foreach ($admins as $admin) {
            $email = $admin->email ?? $admin->attributes['email'] ?? null;
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                EmailService::send($email, $subject, $bodyHtml, $admin->name ?? null);
            }
        }
        $inbox = trim((string) SettingsService::get('admin_notification_email', ''));
        if ($inbox !== '' && filter_var($inbox, FILTER_VALIDATE_EMAIL)) {
            EmailService::send($inbox, $subject, $bodyHtml, null);
        }
    }

    /**
     * Send email to a user if they have a valid email.
     */
    public static function sendUserEmail(User $user, string $subject, string $bodyHtml): void
    {
        if (! EmailService::isConfigured()) {
            return;
        }
        $email = $user->email ?? $user->attributes['email'] ?? null;
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            EmailService::send($email, $subject, $bodyHtml, $user->name ?? null);
        }
    }

    /**
     * Notify all admin users (database notification + email when SMTP configured).
     */
    public static function admins(string $title, string $message, string $type = 'info', ?string $url = null, ?array $meta = null): void
    {
        $admins = User::where('role', User::ROLE_ADMIN)->get();
        foreach ($admins as $admin) {
            $admin->notify(new AppNotification($title, $message, $type, $url, $meta ?? []));
        }
        self::sendAdminEmails($title, self::emailBody($title, $message, $url));
    }

    /**
     * Notify a single user (database notification).
     */
    public static function user(User $user, string $title, string $message, string $type = 'info', ?string $url = null, ?array $meta = null): void
    {
        $user->notify(new AppNotification($title, $message, $type, $url, $meta ?? []));
    }

    /**
     * Notify user of purchase status change: in-app + SMS (for delivered/failed so users get updates).
     */
    public static function notifyUserPurchaseStatus(Purchase $purchase): void
    {
        $user = $purchase->user;
        if (!$user) {
            return;
        }

        $plan = $purchase->data_plan ?? $purchase->network ?? 'Data bundle';
        $ref = $purchase->reference ?? '#' . $purchase->id;
        $amount = number_format((float) $purchase->amount, 2);

        if ($purchase->status === 'delivered') {
            $title = 'Bundle delivered';
            $message = "Your data bundle ({$plan}) for {$purchase->beneficiary} has been delivered. Ref: {$ref}, GH₵ {$amount}.";
            $type = 'success';
        } elseif ($purchase->status === 'failed') {
            $title = 'Bundle failed';
            $reason = $purchase->failure_reason ?? 'Provider could not complete.';
            $message = "Your data order for {$purchase->beneficiary} could not be completed. Ref: {$ref}. {$reason}";
            $type = 'warning';
        } else {
            return;
        }

        self::user($user, $title, $message, $type, '/orders', ['purchase_id' => $purchase->id]);

        $phone = GhanaPhone::normalize($user->phone);
        if ($phone) {
            $sms = SmsService::fromConfig();
            $sms->sendQuickSms([$phone], $message, false);
        }

        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/orders')));
    }

    /**
     * Notify user that a topup was completed: in-app + SMS.
     */
    public static function notifyUserTopupCompleted(User $user, Topup $topup): void
    {
        $amount = number_format((float) $topup->amount, 2);
        $title = 'Wallet topped up';
        $message = "Your wallet has been credited with GH₵ {$amount}. Ref: {$topup->id}. Thank you for using Datatem.";
        self::user($user, $title, $message, 'success', '/dashboard', ['topup_id' => $topup->id]);

        $phone = GhanaPhone::normalize($user->phone);
        if ($phone) {
            $sms = SmsService::fromConfig();
            $sms->sendQuickSms([$phone], $message, false);
        }

        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/dashboard')));
    }

    /**
     * Send announcement SMS to multiple recipients (e.g. broadcast). Uses SmsService.
     *
     * @param array<int, string> $recipients Phone numbers (Ghana format)
     */
    public static function sendAnnouncementSms(array $recipients, string $message): array
    {
        $sms = SmsService::fromConfig();
        return $sms->sendQuickSms($recipients, $message, false);
    }

    /** Notify admins of new user registration (in-app + email). */
    public static function notifyAdminsNewRegistration(User $user): void
    {
        $title = 'New user registration';
        $message = $user->name . ' (' . ($user->email ?: $user->phone ?? 'N/A') . ') just registered.';
        self::admins($title, $message, 'info', url('/admin/users'), ['user_id' => $user->id]);
    }

    /** Send welcome in-app notification and email to new user after registration. */
    public static function notifyUserWelcome(User $user): void
    {
        $title = 'Welcome to ' . (SettingsService::get('site_name') ?: config('app.name', 'Datatem'));
        $message = 'Hi ' . $user->name . ', thank you for signing up. You can now buy data bundles, top up your wallet, and manage your orders from your dashboard. If you have any questions, reach out to our support team.';
        self::user($user, $title, $message, 'success', '/dashboard');
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/dashboard')));
    }

    /** Notify admins of new reseller request (in-app + email). */
    public static function notifyAdminsNewResellerRequest(ResellerRequest $req): void
    {
        $user = $req->user;
        $name = $user ? $user->name : 'Unknown';
        $title = 'New reseller request';
        $message = $name . ' submitted a reseller request.' . ($req->message ? ' Message: ' . $req->message : '');
        self::admins($title, $message, 'info', url('/admin/resellers'), ['reseller_request_id' => $req->id]);
    }

    /** Notify user that their reseller request was submitted (in-app + email). */
    public static function notifyUserResellerRequestSent(User $user): void
    {
        $title = 'Reseller request received';
        $message = 'We have received your reseller application. Our team will review it and get back to you soon.';
        self::user($user, $title, $message, 'info', '/reseller');
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/reseller')));
    }

    /** Notify user that their reseller request was approved (in-app + email). */
    public static function notifyUserResellerApproved(User $user): void
    {
        $title = 'Reseller request approved';
        $message = 'Congratulations! Your reseller application has been approved. You can now access your reseller dashboard and start selling.';
        self::user($user, $title, $message, 'success', '/reseller');
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/reseller')));
    }

    /** Notify user that their complaint was received (email). */
    public static function notifyUserComplaintReceived(User $user, string $ticket): void
    {
        $title = 'Complaint received';
        $message = 'We have received your complaint. Your ticket reference is: ' . $ticket . '. We will look into it and respond as soon as possible.';
        self::user($user, $title, $message, 'info', '/orders');
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/orders')));
    }

    /** Notify user that their data order was placed (in-app + email). */
    public static function notifyUserOrderPlaced(User $user, string $reference, string $planLabel, string $beneficiary, float $amount): void
    {
        $title = 'Order placed';
        $message = "Your data order has been placed. {$planLabel} for {$beneficiary}. Reference: {$reference}, GH₵ " . number_format($amount, 2) . '. You will be notified when the bundle is delivered.';
        self::user($user, $title, $message, 'info', '/orders', ['reference' => $reference]);
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/orders')));
    }

    /** Notify admins that an affiliate requested a withdrawal (in-app + email). */
    public static function notifyAdminsAffiliateWithdrawalRequested(AffiliateWithdrawal $withdrawal): void
    {
        $withdrawal->load('affiliate.user');
        $affiliate = $withdrawal->affiliate;
        $name = $affiliate && $affiliate->user ? $affiliate->user->name : 'Unknown';
        $amount = number_format((float) $withdrawal->amount, 2);
        $title = 'Affiliate withdrawal requested';
        $message = "{$name} requested a withdrawal of GH₵ {$amount}. Review and approve or reject in Affiliates.";
        self::admins($title, $message, 'info', url('/admin/affiliates'), ['affiliate_withdrawal_id' => $withdrawal->id]);
    }

    /** Notify affiliate user that their withdrawal was approved (in-app + email). */
    public static function notifyUserWithdrawalApproved(User $user, AffiliateWithdrawal $withdrawal): void
    {
        $amount = number_format((float) $withdrawal->amount, 2);
        $fee = $withdrawal->fee_amount !== null ? number_format((float) $withdrawal->fee_amount, 2) : '0.00';
        $net = number_format((float) $withdrawal->amount - (float) ($withdrawal->fee_amount ?? 0), 2);
        $title = 'Withdrawal approved';
        $message = "Your withdrawal of GH₵ {$amount} has been approved. Fee: GH₵ {$fee}. Net payout: GH₵ {$net}. Thank you for being an affiliate.";
        self::user($user, $title, $message, 'success', '/referral', ['affiliate_withdrawal_id' => $withdrawal->id]);
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/referral')));
    }

    /** Notify affiliate user that their withdrawal was rejected (in-app + email). */
    public static function notifyUserWithdrawalRejected(User $user, AffiliateWithdrawal $withdrawal): void
    {
        $amount = number_format((float) $withdrawal->amount, 2);
        $notes = $withdrawal->admin_notes ? ' Reason: ' . $withdrawal->admin_notes : '';
        $title = 'Withdrawal request declined';
        $message = "Your withdrawal request of GH₵ {$amount} was not approved.{$notes} You can request again from your referral dashboard.";
        self::user($user, $title, $message, 'warning', '/referral', ['affiliate_withdrawal_id' => $withdrawal->id]);
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/referral')));
    }

    public static function notifyAdminsResellerWithdrawalRequested(ResellerWithdrawal $withdrawal): void
    {
        $withdrawal->load('user');
        $name = $withdrawal->user ? $withdrawal->user->name : 'Unknown';
        $amount = number_format((float) $withdrawal->amount, 2);
        $momo = $withdrawal->momo_number ? (string) $withdrawal->momo_number : '—';
        $title = 'Reseller withdrawal requested';
        $message = "{$name} requested GHS {$amount} to MoMo {$momo}. Review in Admin → Payout requests.";
        self::admins($title, $message, 'info', url('/admin/resellers#withdrawals'), ['reseller_withdrawal_id' => $withdrawal->id]);
    }

    public static function notifyUserResellerWithdrawalApproved(User $user, ResellerWithdrawal $withdrawal): void
    {
        $amount = number_format((float) $withdrawal->amount, 2);
        $fee = $withdrawal->fee_amount !== null ? number_format((float) $withdrawal->fee_amount, 2) : '0.00';
        $net = number_format((float) $withdrawal->amount - (float) ($withdrawal->fee_amount ?? 0), 2);
        $title = 'Reseller withdrawal approved';
        $message = "Your withdrawal of GHS {$amount} has been approved. Fee: GHS {$fee}. Net payout: GHS {$net}.";
        self::user($user, $title, $message, 'success', '/reseller/withdrawals', ['reseller_withdrawal_id' => $withdrawal->id]);
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/reseller/withdrawals')));
    }

    public static function notifyUserResellerWithdrawalRejected(User $user, ResellerWithdrawal $withdrawal): void
    {
        $amount = number_format((float) $withdrawal->amount, 2);
        $notes = $withdrawal->admin_notes ? ' Reason: ' . $withdrawal->admin_notes : '';
        $title = 'Reseller withdrawal declined';
        $message = "Your withdrawal request of GHS {$amount} was not approved.{$notes}";
        self::user($user, $title, $message, 'warning', '/reseller/withdrawals', ['reseller_withdrawal_id' => $withdrawal->id]);
        self::sendUserEmail($user, $title, self::emailBody($title, $message, url('/reseller/withdrawals')));
    }
}

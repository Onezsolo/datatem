<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TemplateLicense;
use App\Models\User;
use App\Services\FeatureSettings;
use App\Services\HandoverService;
use App\Models\TemplatePackage;
use App\Services\TemplateLicenseService;
use App\Services\TemplatePackageService;
use App\Services\TemplateProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeveloperController extends Controller
{
    public function dashboard(TemplateLicenseService $licenses): View
    {
        $status = $licenses->deploymentStatus();

        return view('developer.dashboard', [
            'licensesTotal' => TemplateLicense::query()->count(),
            'licensesActive' => TemplateLicense::query()->active()->count(),
            'licensesRevoked' => TemplateLicense::query()->where('status', 'revoked')->count(),
            'admins' => User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DEVELOPER])->count(),
            'featuresEnabled' => collect(FeatureSettings::all())->filter()->count(),
            'featuresTotal' => count(FeatureSettings::definitions()),
            'deployment' => $status,
            'recentLicenses' => TemplateLicense::query()->latest('id')->limit(5)->get(),
            'productTiers' => TemplateProductService::tiers(),
            'handover' => HandoverService::details(),
            'packagesCount' => TemplatePackage::query()->count(),
            'appliedPackage' => TemplatePackageService::applied(),
            'productVariants' => TemplateProductService::variants(),
        ]);
    }

    public function licenses(): View
    {
        return view('developer.licenses', [
            'licenses' => TemplateLicense::query()->latest('id')->paginate(25),
            'products' => TemplateProductService::variants(),
            'tiers' => TemplateProductService::tiers(),
            'issuedKey' => session('issued_license_key'),
            'issuedClient' => session('issued_license_client'),
        ]);
    }

    public function issue(Request $request, TemplateLicenseService $licenses): RedirectResponse
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:160'],
            'client_email' => ['nullable', 'email', 'max:160'],
            'domain' => ['nullable', 'string', 'max:190'],
            'agreement_reference' => ['nullable', 'string', 'max:120'],
            'product_key' => ['nullable', 'string', Rule::in(array_keys(TemplateProductService::variants()))],
            'license_tier' => ['nullable', 'string', Rule::in(array_keys(TemplateProductService::tiers()))],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($data['product_key'])) {
            $tiers = TemplateProductService::tiersFor($data['product_key']);
            $tierKey = (string) ($data['license_tier'] ?? '');
            $tier = $tiers[$tierKey] ?? null;

            if ($tier === null) {
                return back()->withInput()->withErrors(['license_tier' => 'Choose a license tier for the selected template.']);
            }

            $data['license_tier'] = $tierKey;
            $data['price'] = (float) $tier['price'];
            $data['deployments_allowed'] = $tier['deployments'];
            $data['support_ends_at'] = now()->addDays((int) $tier['support_days']);
        }

        $result = $licenses->issue($data, $request->user());

        return redirect()
            ->route('developer.licenses')
            ->with('status', 'License issued for '.$result['license']->client_name.'.')
            ->with('issued_license_key', $result['key'])
            ->with('issued_license_client', $result['license']->client_name);
    }

    public function revoke(TemplateLicense $license): RedirectResponse
    {
        $license->update(['status' => 'revoked']);

        return back()->with('status', 'License '.$license->key_prefix.' revoked.');
    }

    public function reinstate(TemplateLicense $license): RedirectResponse
    {
        $license->update(['status' => 'active']);

        return back()->with('status', 'License '.$license->key_prefix.' reinstated.');
    }

    public function setDeploymentLicense(Request $request, TemplateLicenseService $licenses): RedirectResponse
    {
        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:64'],
        ]);

        $license = $licenses->setDeploymentKey($data['license_key']);

        if ($license === null) {
            return back()->withErrors(['license_key' => 'That license key does not exist.']);
        }

        return back()->with('status', 'Deployment license set to '.$license->key_prefix.' ('.$license->statusLabel().').');
    }

    public function products(): View
    {
        return view('developer.products', [
            'variants' => TemplateProductService::variants(),
        ]);
    }

    public function productShow(string $product): View
    {
        $variant = TemplateProductService::variant($product);
        abort_if($variant === null, 404);

        return view('developer.product', [
            'key' => $product,
            'variant' => $variant,
        ]);
    }

    public function productPreview(string $product): View
    {
        $variant = TemplateProductService::variant($product);
        abort_if($variant === null, 404);

        return view('developer.product-preview', [
            'key' => $product,
            'variant' => $variant,
            'colours' => TemplateProductService::themeColours($variant),
            'services' => TemplateProductService::serviceLabels($variant),
        ]);
    }

    public function productPackage(string $product, TemplatePackageService $packages, Request $request): RedirectResponse
    {
        $variant = TemplateProductService::variant($product);
        abort_if($variant === null, 404);

        $package = $packages->captureVariant($product, $variant, $request->user());

        return redirect()
            ->route('developer.products.show', $product)
            ->with('status', 'Package '.$package->name.' created. Use Template packages to apply it on a client deployment.');
    }

    public function packages(TemplatePackageService $packages): View
    {
        return view('developer.packages', [
            'packages' => TemplatePackage::query()->latest('id')->paginate(20),
            'applied' => TemplatePackageService::applied(),
        ]);
    }

    public function storePackage(Request $request, TemplatePackageService $packages): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'version' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $package = $packages->capture($data, $request->user());

        return redirect()
            ->route('developer.packages')
            ->with('status', 'Package '.$package->name.' saved from the current setup.');
    }

    public function applyPackage(TemplatePackage $package, TemplatePackageService $packages, Request $request): RedirectResponse
    {
        $result = $packages->apply($package, $request->user());

        return redirect()
            ->route('developer.dashboard')
            ->with('status', 'Package '.$result['package']->name.' applied: theme, features, services, AFA price and '.$result['providers'].' provider source(s).');
    }

    public function destroyPackage(TemplatePackage $package): RedirectResponse
    {
        $package->delete();

        return back()->with('status', 'Package deleted.');
    }

    public function handover(Request $request, TemplateLicenseService $licenses): RedirectResponse
    {
        if (HandoverService::isCompleted()) {
            abort(403, 'Handover already completed.');
        }

        $data = $request->validate([
            'confirmation' => ['required', 'in:HANDOVER'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($licenses->deploymentStatus()['state'] !== 'active') {
            return back()->withErrors(['confirmation' => 'Set an active deployment license before completing handover.']);
        }

        HandoverService::complete($request->user(), $data['note'] ?? null);

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('warning', 'Handover complete. Developer access for this deployment has ended.')
            ->withoutCookie('auth_token');
    }
}
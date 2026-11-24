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
            'handover' => HandoverService::details(),
            'packagesCount' => TemplatePackage::query()->count(),
            'appliedPackage' => TemplatePackageService::applied(),
            'productVariants' => TemplateProductService::variants(),
            'activeProduct' => TemplateProductService::active(),
        ]);
    }

    public function licenses(): View
    {
        return view('developer.licenses', [
            'licenses' => TemplateLicense::query()->latest('id')->paginate(25),
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
            'expires_at' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

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

    public function products(TemplateProductService $products): View
    {
        return view('developer.products', [
            'variants' => TemplateProductService::variants(),
            'active' => TemplateProductService::active(),
        ]);
    }

    public function applyProduct(string $product, TemplateProductService $products, Request $request): RedirectResponse
    {
        $variant = $products->apply($product, $request->user());

        return redirect()
            ->route('developer.products')
            ->with('status', $variant['name'].' template applied: its modules, services and design are now active on this deployment.');
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
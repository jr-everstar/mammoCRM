<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $query = Asset::query()->latest();

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('assets.index', [
            'assets' => $query->paginate(20)->withQueryString(),
            'types' => Asset::TYPES,
            'statuses' => Asset::STATUSES,
            'sensorSides' => Asset::SENSOR_SIDES,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->canManageCrm(), 403);

        return view('assets.form', [
            'asset' => null,
            'types' => Asset::TYPES,
            'statuses' => Asset::STATUSES,
            'sensorSides' => Asset::SENSOR_SIDES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageCrm(), 403);

        $data = $this->validated($request);
        $data['asset_tag'] = $this->nextAssetTag($data['type'], $data['side'] ?? null);

        Asset::create($data);

        return to_route('assets.index')->with('status', 'Asset saved.');
    }

    public function edit(Request $request, Asset $asset): View
    {
        abort_unless($request->user()->canManageCrm(), 403);

        return view('assets.form', [
            'asset' => $asset,
            'types' => Asset::TYPES,
            'statuses' => Asset::STATUSES,
            'sensorSides' => Asset::SENSOR_SIDES,
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        abort_unless($request->user()->canManageCrm(), 403);

        $asset->update($this->validated($request, $asset));

        return to_route('assets.index')->with('status', 'Asset updated.');
    }

    private function validated(Request $request, ?Asset $asset = null): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', Asset::TYPES)],
            'side' => ['nullable', 'required_if:type,sensor', 'in:'.implode(',', Asset::SENSOR_SIDES)],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'model_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', Asset::STATUSES)],
            'condition' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['type'] !== 'sensor') {
            $data['side'] = null;
        }

        return $data;
    }

    private function nextAssetTag(string $type, ?string $side = null): string
    {
        $prefix = match ($type) {
            'ipad' => 'IPAD',
            'sensor' => $side === 'right' ? 'SEN-R' : 'SEN-L',
            'charger' => 'CHG',
            'cable' => 'CBL',
            'case' => 'CASE',
            'accessory' => 'ACC',
            default => 'OTH',
        };

        $nextNumber = 1;

        Asset::withTrashed()
            ->where('asset_tag', 'like', $prefix.'-%')
            ->pluck('asset_tag')
            ->each(function (string $assetTag) use (&$nextNumber, $prefix): void {
                if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', $assetTag, $matches)) {
                    $nextNumber = max($nextNumber, ((int) $matches[1]) + 1);
                }
            });

        do {
            $assetTag = $prefix.'-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Asset::withTrashed()->where('asset_tag', $assetTag)->exists());

        return $assetTag;
    }
}

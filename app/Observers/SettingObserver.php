<?php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    /**
     * Handle the Setting "saved" event.
     * Fires after both creating and updating.
     */
    public function saved(Setting $setting): void
    {
        $this->clearSettingCache($setting);
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        $this->clearSettingCache($setting);
    }

    /**
     * Clear cache for the given setting.
     */
    protected function clearSettingCache(Setting $setting): void
    {
        // Clear individual setting cache
        Cache::forget("setting.{$setting->name}");

        // Clear all bulk setting caches
        Setting::clearBulkCaches();
    }
}

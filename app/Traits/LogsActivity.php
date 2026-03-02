<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            $description = method_exists($model, 'getActivityDescription')
                ? $model->getActivityDescription('CREATE')
                : 'Created ' . self::getModelName($model);

            $properties = [
                'old' => null,
                'new' => $model->getAttributes(),
            ];

            if (method_exists($model, 'getActivityProperties')) {
                $properties = array_merge($properties, $model->getActivityProperties('CREATE'));
            }

            self::logActivity($model, 'CREATE', $description, $properties);
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            // Remove updated_at from changes if it's the only change
            if (count($changes) === 1 && array_key_exists('updated_at', $changes)) {
                return;
            }

            $original = [];
            foreach ($changes as $key => $value) {
                $original[$key] = $model->getOriginal($key);
            }

            $description = method_exists($model, 'getActivityDescription')
                ? $model->getActivityDescription('UPDATE')
                : 'Updated ' . self::getModelName($model);

            $properties = [
                'old' => $original,
                'new' => $changes,
            ];

            if (method_exists($model, 'getActivityProperties')) {
                $properties = array_merge($properties, $model->getActivityProperties('UPDATE'));
            }

            self::logActivity($model, 'UPDATE', $description, $properties);
        });

        static::deleted(function (Model $model) {
            $description = method_exists($model, 'getActivityDescription')
                ? $model->getActivityDescription('DELETE')
                : 'Deleted ' . self::getModelName($model);

            $properties = [
                'attributes' => $model->getAttributes(),
            ];

            if (method_exists($model, 'getActivityProperties')) {
                $properties = array_merge($properties, $model->getActivityProperties('DELETE'));
            }

            self::logActivity($model, 'DELETE', $description, $properties);
        });
    }

    protected static function logActivity(Model $model, string $action, string $description, array $properties = [])
    {
        $user = Auth::user() ?? Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;

        // Determine if user is Admin or User based on class
        $isAdmin = $user && $user instanceof \App\Models\Admin;

        ActivityLog::create([
            'user_id' => !$isAdmin ? $userId : null,
            'admin_id' => $isAdmin ? $userId : null,
            'action' => $action,
            'description' => $description,
            'subject_type' => get_class($model),
            'subject_id' => $model->getKey(),
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected static function getModelName(Model $model): string
    {
        return Str::headline(class_basename($model));
    }
}

<?php

use App\Models\UserActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Jenssegers\Agent\Agent;

/**
 * undocumented class
 */
class Helper
{
	public static function logAction(string $action, mixed $modelInstance): UserActivity
	{
		$agent = new Agent();

		return UserActivity::create([
			'user_id' => auth()->id(),
			'action' => $action,
			'model' => $modelInstance::class,
			'model_id' => $modelInstance->id,
			'value' => json_encode($modelInstance->getDirty()),
			'ip' => request()->ip(),
			'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
			'device' => $agent->device(),
			'os' => $agent->platform() . ' ' . $agent->version($agent->platform())
		]);
	}

	public static function logAuth(string $action): UserActivity
	{
		$agent = new Agent();

		return UserActivity::create([
			'user_id' => auth()->id(),
			'action' => $action,
			'ip' => request()->ip(),
			'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
			'device' => $agent->device(),
			'os' => $agent->platform() . ' ' . $agent->version($agent->platform())
		]);
	}

	public static function getSuccessCrudResponse(string $event, string $dataType, string $id_for_human): JsonResponse|RedirectResponse
	{
		$message = __("notification.data_{$event}", ['type' => $dataType, 'name' => "<b>{$id_for_human}</b>"]);
		$color = $event == 'deleted' ? 'warning' : 'success';

		if (request()->wantsJson()) {
			return response()->json([
				'notifications' => [[
					'messageHtml' => $message,
					'color' => $color
				]]
			]);
		}

		return back()->with('notifications', [
			[$message, $color]
		]);
	}
}

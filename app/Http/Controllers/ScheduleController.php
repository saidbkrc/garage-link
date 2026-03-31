<?php

namespace App\Http\Controllers;

use App\Models\Command;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\Scene;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index()
    {
        $user     = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        $schedules = Schedule::with(['device', 'scene'])
            ->where('dealer_id', $dealerId)
            ->orderBy('time')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'name'             => $s->name,
                'time'             => $s->time,
                'days'             => $s->days ?? [],
                'command_slug'     => $s->command_slug,
                'params'           => $s->params ?? [],
                'is_active'        => $s->is_active,
                'last_triggered_at'=> $s->last_triggered_at?->toISOString(),
                'device'           => $s->device ? ['id' => $s->device->id, 'name' => $s->device->name] : null,
                'scene'            => $s->scene  ? ['id' => $s->scene->id,  'name' => $s->scene->name]  : null,
            ]);

        $devices = Device::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->with('deviceType')
            ->get()
            ->map(fn($d) => [
                'id'   => $d->id,
                'name' => $d->name,
                'type' => $d->deviceType?->slug,
                'icon' => $d->deviceType?->icon,
            ]);

        $scenes = Scene::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->get(['id', 'name', 'icon', 'color']);

        $commands = Command::where('is_active', true)
            ->whereNotIn('category', ['system', 'scan'])
            ->orderBy('order')
            ->get(['id', 'slug', 'name', 'category']);

        return Inertia::render('Schedules/Index', [
            'schedules' => $schedules,
            'devices'   => $devices,
            'scenes'    => $scenes,
            'commands'  => $commands,
            'user'      => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('dealer')->user();

        $request->validate([
            'name'         => 'required|string|max:255',
            'time'         => 'required|date_format:H:i',
            'days'         => 'required|array|min:1',
            'days.*'       => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'device_id'    => 'nullable|exists:devices,id',
            'scene_id'     => 'nullable|exists:scenes,id',
            'command_slug' => 'nullable|string|exists:commands,slug',
            'params'       => 'nullable|array',
        ], [
            'name.required'      => 'Zamanlama adı gereklidir',
            'time.required'      => 'Saat seçiniz',
            'time.date_format'   => 'Saat formatı HH:MM olmalıdır',
            'days.required'      => 'En az bir gün seçiniz',
            'days.min'           => 'En az bir gün seçiniz',
        ]);

        Schedule::create([
            'dealer_id'    => $user->dealer_id,
            'device_id'    => $request->device_id,
            'scene_id'     => $request->scene_id,
            'name'         => $request->name,
            'time'         => $request->time,
            'days'         => $request->days,
            'command_slug' => $request->command_slug,
            'params'       => $request->params ?? [],
            'is_active'    => true,
        ]);

        return redirect()->back()->with('success', 'Zamanlama oluşturuldu');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $user = Auth::guard('dealer')->user();

        if ($schedule->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $request->validate([
            'name'         => 'sometimes|string|max:255',
            'time'         => 'sometimes|date_format:H:i',
            'days'         => 'sometimes|array|min:1',
            'days.*'       => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'device_id'    => 'nullable|exists:devices,id',
            'scene_id'     => 'nullable|exists:scenes,id',
            'command_slug' => 'nullable|string|exists:commands,slug',
            'params'       => 'nullable|array',
            'is_active'    => 'sometimes|boolean',
        ]);

        $schedule->update($request->only([
            'name', 'time', 'days', 'device_id', 'scene_id',
            'command_slug', 'params', 'is_active',
        ]));

        return redirect()->back()->with('success', 'Zamanlama güncellendi');
    }

    public function destroy(Schedule $schedule)
    {
        $user = Auth::guard('dealer')->user();

        if ($schedule->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $schedule->delete();

        return redirect()->back()->with('success', 'Zamanlama silindi');
    }
}

<?php
namespace App\Http\Controllers;
use App\Models\Device;
use App\Models\DeviceModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class DeviceController extends Controller {
 public function index(Request $request): JsonResponse {$q=Device::query();foreach(['devices_type','device_service'] as $f){if($request->filled($f))$q->where($f,$request->string($f));}if($request->filled('date_from'))$q->whereDate('registered_at','>=',$request->string('date_from'));if($request->filled('date_to'))$q->whereDate('registered_at','<=',$request->string('date_to'));if($request->filled('search'))$q->where('recognized_text','like','%'.$request->string('search').'%');return response()->json(['devices'=>$q->latest('registered_at')->latest('id')->get()]);}
 public function models(Request $r): JsonResponse {$q=DeviceModel::query();if($r->filled('devices_type'))$q->where('devices_type',$r->string('devices_type'));if($r->filled('device_service'))$q->where('device_service',$r->string('device_service'));return response()->json(['models'=>$q->orderBy('devices_name')->get()]);}
 public function storeModel(Request $r): JsonResponse{return response()->json(['model'=>DeviceModel::create($this->modelData($r))],201);}
 public function updateModel(Request $r,DeviceModel $deviceModel): JsonResponse {$deviceModel->update($this->modelData($r));return response()->json(['model'=>$deviceModel]);}
 public function destroyModel(DeviceModel $deviceModel): JsonResponse {$deviceModel->delete();return response()->json(status:204);}
 public function store(Request $r): JsonResponse{return response()->json(['device'=>Device::create($this->deviceData($r))],201);}
 public function update(Request $r,Device $device): JsonResponse {$device->update($this->deviceData($r));return response()->json(['device'=>$device]);}
 public function destroy(Device $device): JsonResponse {$device->delete();return response()->json(status:204);}
 private function modelData(Request $r):array{return $r->validate(['devices_name'=>['required','string','max:120'],'devices_type'=>['required','in:tuner,modem'],'device_service'=>['required','in:internet,television']]);}
 private function deviceData(Request $r):array{$d=$r->validate(['recognized_text'=>['required','string'],'device_model_id'=>['required','exists:device_models,id'],'registered_at'=>['required','date']]);$m=DeviceModel::findOrFail($d['device_model_id']);return [...$d,'devices_name'=>$m->devices_name,'devices_type'=>$m->devices_type,'device_service'=>$m->device_service];}
}
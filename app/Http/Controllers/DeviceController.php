<?php
namespace App\Http\Controllers;
use App\Models\Device;
use App\Models\DeviceModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class DeviceController extends Controller {
 public function index(): JsonResponse {return response()->json(['devices'=>Device::latest('registered_at')->get()]);}
 public function models(): JsonResponse {return response()->json(['models'=>DeviceModel::orderBy('devices_name')->get()]);}
 public function storeModel(Request $request): JsonResponse {$data=$request->validate(['devices_name'=>['required','string','max:120'],'devices_type'=>['required','in:tuner,modem'],'device_service'=>['required','in:internet,television']]);return response()->json(['model'=>DeviceModel::create($data)],201);}
 public function store(Request $request): JsonResponse {$data=$request->validate(['recognized_text'=>['required','string'],'device_model_id'=>['required','exists:device_models,id'],'registered_at'=>['required','date']]);$model=DeviceModel::findOrFail($data['device_model_id']);$device=Device::create([...$data,'devices_name'=>$model->devices_name,'devices_type'=>$model->devices_type,'device_service'=>$model->device_service]);return response()->json(['device'=>$device],201);}
}
<?php
namespace App\Http\Controllers;
use App\Models\Device;use App\Models\DeviceModel;use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;
final class DeviceController extends Controller{
 public function index(Request $r):JsonResponse{return response()->json(['devices'=>$this->query($r)->get()]);}
 public function models(Request $r):JsonResponse{$q=DeviceModel::query();foreach(['devices_type','device_service']as$f)if($r->filled($f))$q->where($f,$r->string($f));return response()->json(['models'=>$q->orderBy('devices_name')->get()]);}
 public function storeModel(Request $r):JsonResponse{return response()->json(['model'=>DeviceModel::create($this->modelData($r))],201);}
 public function updateModel(Request $r,DeviceModel $deviceModel):JsonResponse{$deviceModel->update($this->modelData($r));return response()->json(['model'=>$deviceModel]);}
 public function destroyModel(DeviceModel $deviceModel):JsonResponse{$deviceModel->delete();return response()->json(status:204);}
 public function store(Request $r):JsonResponse{return response()->json(['device'=>Device::create($this->deviceData($r))],201);}
 public function update(Request $r,Device $device):JsonResponse{$device->update($this->deviceData($r));return response()->json(['device'=>$device]);}
 public function destroy(Device $device):JsonResponse{$device->delete();return response()->json(status:204);}
 public function export(Request $r):\Symfony\Component\HttpFoundation\StreamedResponse{$items=$this->query($r)->get();return response()->streamDownload(function()use($items){$out=fopen('php://output','w');fwrite($out,"sep=;\r\n");$encode=fn(array $row)=>array_map(fn($value)=>iconv('UTF-8','Windows-1251//TRANSLIT',$value),$row);fputcsv($out,$encode(['Дата','Текст','Модель','Тип','Послуга']),';');foreach($items as$d)fputcsv($out,$encode([$d->registered_at->timezone('Europe/Kyiv')->format('d.m.Y H:i'),$d->recognized_text,$d->devices_name,$d->devices_type,$d->device_service]),';');fclose($out);},'equipment.csv',['Content-Type'=>'text/csv; charset=UTF-8']);}
 private function query(Request $r){$q=Device::query();foreach(['devices_type','device_service']as$f)if($r->filled($f))$q->where($f,$r->string($f));if($r->filled('date_from'))$q->whereDate('registered_at','>=',$r->string('date_from'));if($r->filled('date_to'))$q->whereDate('registered_at','<=',$r->string('date_to'));if($r->filled('search'))$q->where('recognized_text','like','%'.$r->string('search').'%');return$q->latest('registered_at')->latest('id');}
 private function modelData(Request $r):array{return$r->validate(['devices_name'=>['required','string','max:120'],'devices_type'=>['required','in:tuner,modem'],'device_service'=>['required','in:internet,television']]);}
 private function deviceData(Request $r):array{$d=$r->validate(['recognized_text'=>['required','string'],'device_model_id'=>['required','exists:device_models,id'],'registered_at'=>['required','date']]);$m=DeviceModel::findOrFail($d['device_model_id']);return[...$d,'devices_name'=>$m->devices_name,'devices_type'=>$m->devices_type,'device_service'=>$m->device_service];}
}
<?php

namespace App\Http\Controllers\Bank;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CoreController extends Controller
{
    public function resource(Request $request)
    {
        $search = $request->get('search', '');
        $conn = $request->get('conn', 'test');
        $type = $request->get('type', 'search');
        $id = $request->get('id', '');
        $dev_pdo = $this->getConnPdo('core', 'dev');
        $online_pdo = $this->getConnPdo('core', $conn);
        if (!empty($id)) {
            $data = $this->getData(DB::setPdo($dev_pdo)->table('resource')->find($id));
            if (count(DB::setPdo($online_pdo)->table('resource')->where('name', $data['name'])->get()) == 0) {
                DB::setPdo($online_pdo)->table('resource')->insert($data);
            }
            return redirect()->back();
        }
        if ($type == 'sync') {
            if (!empty($search)) {
                $name = '%' . $search . '%';
                $dev_res = DB::setPdo($dev_pdo)->table('resource')->where('name', 'like', $name)->get()->keyBy('name');
                $online_res = DB::setPdo($online_pdo)->table('resource')->where('name', 'like', $name)->get()->keyBy('name');
                foreach ($online_res as $name => $item) {
                    if ($item->url != $dev_res[$name]->url) {
                        \DB::table('resource')->where('id', $item->id)->update(['url' => $dev_res[$name]->url]);
                    }
                }
            }
            return redirect()->back();
        }
        if ($type == 'search') {
            $dev_res = $online_res = [];
            if (!empty($search)) {
                $name = '%' . $search . '%';
                $dev_res = DB::setPdo($dev_pdo)->table('resource')->where('name', 'like', $name)->get()->keyBy('name');
                $online_res = DB::setPdo($online_pdo)->table('resource')->where('name', 'like', $name)->get()->keyBy('name');
            }
        }
        return view('bank.core.resource', compact('dev_res', 'online_res', 'search', 'type', 'conn'));
    }

    protected function getData($item)
    {
        $data = json_decode(json_encode($item), true);
        unset($data['id']);
        return $data;
    }

    protected function updateResource()
    {
        $likes = ['wordshare_new', 'listening_share'];
        $like = $likes[0] . '_%';
        config(['database.default' => 'dev']);
        $resource = \DB::table('resource')->where('name', 'like', $like)->get();
        config(['database.default' => $this->argument('conn')]);
        $this->info('total ' . count($resource));
        foreach ($resource as $item) {
            $name = $item->name;
            $data = \DB::table('resource')->where('name', $name)->get();
            if (count($data) == 1) {
                $value = $data[0];
                if ($value->url == $item->url) {
                    $this->line($name . ' Same.');
                } else {
                    \DB::table('resource')->where('id', $value->id)->update(['url' => $item->url]);
                    $this->info($name . ' Done.');
                }
                continue;
            }
            $this->error($name . ' Not Exists.');
        }
    }

}
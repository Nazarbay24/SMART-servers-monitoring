<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function index() {
        $connections = DB::table('connection')->where('license', '!=', 'test')->get()->toArray();

        $data = [];

        $curTime = time();

        foreach ($connections as $connection) {
            Config::set('database.connections.smart_mektep_db.database', $connection->database);
            Config::set('database.connections.smart_mektep_db.username', $connection->host_user);
            Config::set('database.connections.smart_mektep_db.password', $connection->host_password);

            DB::purge('smart_mektep_db');

            $passLog = DB::connection('smart_mektep_db')
                ->table('loc_pass_log')
                ->select('created_at')
                ->orderBy('created_at', 'desc')
                ->first();

            $foodLog = DB::connection('smart_mektep_db')
                ->table('loc_food_log')
                ->select('created_at')
                ->orderBy('created_at', 'desc')
                ->first();

            $elibLog = DB::connection('smart_mektep_db')
                ->table('loc_elib_log')
                ->select('created_at')
                ->orderBy('created_at', 'desc')
                ->first();


            if($passLog && strtotime($passLog->created_at) > ($curTime - 3600)) {
                $colorClass = 'table-success';
            }
            else if($passLog && strtotime($passLog->created_at) > ($curTime - 7200)) {
                $colorClass = 'table-warning';
            }
            else {
                $colorClass = 'table-danger';
            }

            $data[] = [
                "mektepName" => $connection->owner,
                "license" => $connection->license,
                "status" => $connection->status,
                "mektepId" => $connection->mektep_id,
                "passLog" => $passLog?->created_at,
                "foodLog" => $foodLog?->created_at,
                "elibLog" => $elibLog?->created_at,
                "colorClass" => $colorClass
            ];
        }

        usort($data, function($a, $b) {
            $dateA = strtotime($a['passLog']);
            $dateB = strtotime($b['passLog']);
            return $dateB - $dateA;
        });

        return view('monitoring', ["data" => $data]);
    }
}

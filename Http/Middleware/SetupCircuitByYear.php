<?php

namespace App\Modules\Enroll\Http\Middleware;

use Carbon\Carbon;
use Closure;

class SetupCircuitByYear
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->session()->has('is_admin') && $request->hasCookie('enroll_master_credential')) {
            $department = $request->session()->get('user_info.dept_id');
            $circuit = (new \App\Modules\Enroll\Models\CircuitDesigns())->getDepartmentCurrentCircuit($department);

            // 开设设置部门的报名流程
            if (is_null($circuit))
                return redirect()->to('/enroll/setup');

            if ($circuit->getAttribute($circuit->getUpdatedAtColumn())->diffInYears(Carbon::now()) > 1)
                return redirect()->to('/enroll/setup');
        }

        return $next($request);
    }
}

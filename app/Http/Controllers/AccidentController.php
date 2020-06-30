<?php

namespace App\Http\Controllers;

use App\Death;
use App\Injury;
use App\Section;
use App\Accident;
use App\OfficerConcerned;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OfficerConcernedNotification;
use App\OfficerContact;

class AccidentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('accident.index',['accidents' =>  Accident::with('section')->orderBy('created_at', 'DESC')->get()]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('accident.create',[
            'death_types' => Death::get_dropdown_menu(), 
            'injury_types' => Injury::get_dropdown_menu(),
            'sections' => Section::get_dropdown_menu(),
            'belonged_quarter' => get_quarters_dropdown(),
            'nature_of_accident' => get_nature_of_accident_dropdown(),
            'resposible_designation' => get_responsible_designation_dropdown()
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
       $data = $request->validate([
                'time_of_accident' => ['required', 'date_format:Y-m-d H:i:s'],
                'occured_at' => ['required', 'string'],
                'section_id' => ['required', 'exists:sections,id'],
                'train' => ['required', 'string'],
                'train_load' => ['sometimes', 'required', 'string'],
                'driver_name' => ['sometimes', 'required', 'string'],
                'guard_name' => ['sometimes', 'required', 'string'],
                'received_from_control_location' => ['sometimes', 'required', 'string'],
                'received_from_control_time' => ['sometimes', 'required', 'date_format:Y-m-d H:i:s'],
                'accident_subject' => ['required', 'string'],
                'brief_particulars' => ['sometimes', 'required', 'string'],
                'damages' => ['sometimes', 'required', 'string'],
                'cause_of_the_accident' => ['sometimes', 'required', 'string'],
                'assistance_required' => ['sometimes', 'required', 'string'],
                'nature_of_accident' => ['sometimes', 'required', Rule::in(array_column(get_nature_of_accident_dropdown(), 'value'))],
                'belonged_quarter' => ['sometimes', 'required', Rule::in(array_column(get_quarters_dropdown(), 'value'))],
                'responsible_designation' => ['sometimes', 'required', Rule::in(array_column(get_responsible_designation_dropdown(), 'value'))],
                'time_spent_for_line_clear' => ['sometimes', 'required', 'string', 'date_format:Y-m-d H:i:s'],
                'line_closure_time' => ['sometimes', 'required', 'date_format:Y-m-d H:i:s'],
                'death_id.*.id' => ['sometimes', 'required', 'exists:deaths,id', 'distinct'],
                'death_number.*.value' => ['sometimes', 'required', 'string'],
                'injury_id.*.id' => ['sometimes', 'required', 'exists:injuries,id', 'distinct'],
                'injury_number.*.value' => ['sometimes', 'required', 'string'],
            ]);
            
            $section = Section::find($request['section_id']);

            $last_ref_no = Accident::where('section_id', $section->id)->orderBy('created_at', 'desc')->first();
            
            if (isset($last_ref_no->id)) {
                
                $section_btw = explode("-", $section->between);
                $list = explode(".", $last_ref_no->reference_number);

                $request->merge(['reference_number' => $section_btw[0] . ".ACC." . ($list[2]+1).".". date('y')]);
                
                $accident_log = Accident::create([
                'time_of_accident' => $request['time_of_accident'],
                'occured_at' => $request['occured_at'],
                'section_id' => $request['section_id'],
                'train' => $request['train'],
                'train_load' => $request['train_load'],
                'driver_name' => $request['driver_name'],
                'guard_name' => $request['guard_name'],
                'received_from_control_location' => $request['received_from_control_location'],
                'received_from_control_time' => $request['received_from_control_time'],
                'accident_subject' => $request['accident_subject'],
                'brief_particulars' => $request['brief_particulars'],
                'damages' => $request['damages'],
                'cause_of_accident' => $request['cause_of_the_accident'],
                'assistance_required' => $request['assistance_required'],
                'nature_of_accident' => $request['nature_of_accident'],
                'belonged_quarter' => $request['belonged_quarter'],
                'responsible_designation' =>$request['responsible_designation'],
                'time_spent_for_line_clear' => $request['time_spent_for_line_clear'],
                'line_closure_time' => $request['line_closure_time'],
                'reference_number' => $request['reference_number'],
                'user_id' => auth()->user()->id
                ]);
                
                $deaths = array_combine(array_column($request['death_id'], 'id'), array_column($request['death_number'], 'value'));
                $injuries = array_combine(array_column($request['injury_id'], 'id'), array_column($request['injury_number'], 'value'));
                
                foreach ($deaths as $key => $value) {

                    $accident_log->deaths()->attach($key, ['number' => $value]);
                }
                
                foreach ($injuries as $key => $value) {

                    $accident_log->injuries()->attach($key, ['number' => $value]);
                }
                
                return redirect('/accident')->with(['success' => 'Accident Log Created Successful']);
            }

            $section_btw = explode("-", $section->between);
            $request->merge(['reference_number' => $section_btw[0] . ".ACC." . "1." . date('y')]);
            $accident_log = Accident::create([
                'time_of_accident' => $request['time_of_accident'],
                'occured_at' => $request['occured_at'],
                'section_id' => $request['section_id'],
                'train' => $request['train'],
                'train_load' => $request['train_load'],
                'driver_name' => $request['driver_name'],
                'guard_name' => $request['guard_name'],
                'received_from_control_location' => $request['received_from_control_location'],
                'received_from_control_time' => $request['received_from_control_time'],
                'accident_subject' => $request['accident_subject'],
                'brief_particulars' => $request['brief_particulars'],
                'damages' => $request['damages'],
                'cause_of_accident' => $request['cause_of_the_accident'],
                'assistance_required' => $request['assistance_required'],
                'nature_of_accident' => $request['nature_of_accident'],
                'belonged_quarter' => $request['belonged_quarter'],
                'responsible_designation' =>$request['responsible_designation'],
                'time_spent_for_line_clear' => $request['time_spent_for_line_clear'],
                'line_closure_time' => $request['line_closure_time'],
                'reference_number' => $request['reference_number'],
                'user_id' => auth()->user()->id
            ]);
            
            $deaths = array_combine(array_column($request['death_id'],'id'), array_column($request['death_number'], 'value'));
            $injuries = array_combine(array_column($request['injury_id'],'id'), array_column($request['injury_number'],'value'));
            
            foreach ($deaths as $key => $value) {

                $accident_log->deaths()->attach($key, ['number' => $value]);
            }
            
            foreach ($injuries as $key => $value) {

                $accident_log->injuries()->attach($key, ['number' => $value]);
            }

            // $officerconcerd_mobile_phone = OfficerContact::pluck('phone_no')->get();

            // Notification::route('sms', $officerconcerd_mobile_phone )->notify(new OfficerConcernedNotification($accident_log));

            return redirect('/accident')->with(['success' => 'Accident Log Created Successful']);


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Accident  $accident
     * @return \Illuminate\Http\Response
     */
    public function show(Accident $accident)
    {
        return view('accident.show', [
            'accident' => $accident, 
            'belonged_quarter' => get_quarters_dropdown(),
            'nature_of_accident' => get_nature_of_accident_dropdown(),
            'officers'=> OfficerConcerned::all()
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Accident  $accident
     * @return \Illuminate\Http\Response
     */
    public function edit(Accident $accident)
    {   
        

        return view('accident.edit',[
            'death_types' => Death::get_dropdown_menu(), 
            'injury_types' => Injury::get_dropdown_menu(),
            'sections' => Section::get_dropdown_menu(),
            'belonged_quarter' => get_quarters_dropdown(),
            'nature_of_accident' => get_nature_of_accident_dropdown(),
            'resposible_designation' => get_responsible_designation_dropdown(),
            'accident' => $accident,
            'deaths' => $accident->deaths,
            'injuries' => $accident->injuries,
            'section_selected' => $accident->section,
            'belonged_quarter_selected' => get_quarters_dropdown()[array_search($accident->belonged_quarter, array_column(get_quarters_dropdown(), 'value'))],
            'nature_of_accident_selected' => get_nature_of_accident_dropdown()[array_search($accident->ature_of_accident, array_column( get_nature_of_accident_dropdown(), 'value'))],
            'resposible_designation_selected' => get_responsible_designation_dropdown()[array_search($accident->resposible_designation, array_column(get_responsible_designation_dropdown(), 'value'))],
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Accident  $accident
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Accident $accident)
    {
        $data = $request->validate([
            'time_of_accident' => ['required', 'date_format:Y-m-d H:i:s'],
            'occured_at' => ['required', 'string'],
            'section_id' => ['required', 'exists:sections,id'],
            'train' => ['required', 'string'],
            'train_load' => ['sometimes', 'required', 'string'],
            'driver_name' => ['sometimes', 'required', 'string'],
            'guard_name' => ['sometimes', 'required', 'string'],
            'received_from_control_location' => ['sometimes', 'required', 'string'],
            'received_from_control_time' => ['sometimes', 'required', 'date_format:Y-m-d H:i:s'],
            'accident_subject' => ['required', 'string'],
            'brief_particulars' => ['sometimes', 'required', 'string'],
            'damages' => ['sometimes', 'required', 'string'],
            'cause_of_the_accident' => ['sometimes', 'required', 'string'],
            'assistance_required' => ['sometimes', 'required', 'string'],
            'nature_of_accident' => ['sometimes', 'required', Rule::in(array_column(get_nature_of_accident_dropdown(), 'value'))],
            'belonged_quarter' => ['sometimes', 'required', Rule::in(array_column(get_quarters_dropdown(), 'value'))],
            'responsible_designation' => ['sometimes', 'required', Rule::in(array_column(get_responsible_designation_dropdown(), 'value'))],
            'time_spent_for_line_clear' => ['sometimes', 'required', 'string', 'date_format:Y-m-d H:i:s'],
            'line_closure_time' => ['sometimes', 'required', 'date_format:Y-m-d H:i:s'],
            'death_id.*.id' => ['sometimes', 'required', 'exists:deaths,id', 'distinct'],
            'death_number.*.value' => ['sometimes', 'required', 'string'],
            'injury_id.*.id' => ['sometimes', 'required', 'exists:injuries,id', 'distinct'],
            'injury_number.*.value' => ['sometimes', 'required', 'string'],
        ]);

        $accident_log = $accident->update([
            'time_of_accident' => $request['time_of_accident'],
            'occured_at' => $request['occured_at'],
            'section_id' => $request['section_id'],
            'train' => $request['train'],
            'train_load' => $request['train_load'],
            'driver_name' => $request['driver_name'],
            'guard_name' => $request['guard_name'],
            'received_from_control_location' => $request['received_from_control_location'],
            'received_from_control_time' => $request['received_from_control_time'],
            'accident_subject' => $request['accident_subject'],
            'brief_particulars' => $request['brief_particulars'],
            'damages' => $request['damages'],
            'cause_of_accident' => $request['cause_of_the_accident'],
            'assistance_required' => $request['assistance_required'],
            'nature_of_accident' => $request['nature_of_accident'],
            'belonged_quarter' => $request['belonged_quarter'],
            'responsible_designation' =>$request['responsible_designation'],
            'time_spent_for_line_clear' => $request['time_spent_for_line_clear'],
            'line_closure_time' => $request['line_closure_time'],
            'reference_number' => $request['reference_number'],
            'user_id' => auth()->user()->id
        ]);
        
        $deaths = array_combine(array_column($request['death_id'],'id'), array_column($request['death_number'], 'value'));
        $injuries = array_combine(array_column($request['injury_id'],'id'), array_column($request['injury_number'],'value'));
        
        foreach ($deaths as $key => $value) {

            $accident->deaths()->sync($key, ['number' => $value]);
        }
        
        foreach ($injuries as $key => $value) {

            $accident->injuries()->sync($key, ['number' => $value]);
        }

        // $officerconcerd_mobile_phone = OfficerContact::pluck('phone_no')->get();

        // Notification::route('sms', $officerconcerd_mobile_phone )->notify(new OfficerConcernedNotification($accident_log));


        return redirect()->back()->with(['success' => 'Accident Log Updated Successful']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Accident  $accident
     * @return \Illuminate\Http\Response
     */
    public function destroy(Accident $accident)
    {
        $accident->delete();

        return redirect()->back()->with(['success' => 'Accident Log Deleted Successful']);
    }
}
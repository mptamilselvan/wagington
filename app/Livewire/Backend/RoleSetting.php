<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSetting extends Component
{
    public $permissions,$roles=[],$grant=[],$selectedRole='';
    public function index()
    {
        return view('backend.role-setting');
    }
    public function mount()
    {
        try
        {
            $roles=Role::where('id', '>', 1)->get();
            foreach ($roles as $key => $value) {
                $this->roles[$key]=array(
                                            'option'=>$value['name'],
                                            'value' => $value['id']
                                );
            }
            $this->permissions=Permission::get();
        }
       catch(Exception $e)
       {
            dd($e->getMessage() . $e->getCode());
       }
    }
    public function render()
    {
        $this->selectRole();
        return view('livewire.backend.role-setting');
    }
    public function save()
    {
       try
       {
        $role=Role::where('id',$this->selectedRole)->first();
        $role->syncPermissions($this->grant);
       }
       catch(Exception $e)
       {
            dd($e->getMessage() . $e->getCode());
       }
    }
    public function selectRole()
    {
        try
        {
            $this->grant=array();
            $roleId=($this->selectedRole)?$this->selectedRole:3;
    
            $currentPermissions=Permission::SELECT('role_has_permissions.permission_id','name')
            ->JOIN('role_has_permissions','permissions.id','=','role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id',$roleId)->get(); //dd($currentPermissions);
            //dd($currentPermissions);
            foreach ($currentPermissions as $key => $value) {
                $this->grant[$value->permission_id]=$value->permission_id;
            }
        }
        catch(Exception $e)
        {
            dd($e->getMessage() . $e->getCode());
        }
       
    }
}


<div class="space-y-[36px] x-lay mt-[20px] mb-[40px] lg:w-1/2">
@component('components.header',
['title'=>'Roles and Permissions ',
'type'=>'titleOnly'])
@endcomponent
  <div class="w-1/2">

    @component('components.dropdown-component' , [
    'label' => 'Select a Role',
    'wireModel' => 'selectedRole',
    'id' => 'selectedRole',
    'options' => $roles,
    'isDefer' => false,
    'wireClickFn' => 'selectRole'
    ])
    @endcomponent
  </div>


  <div class="table-wrapper">
    <table class="table ">
      <thead class="bg-gray-light">
        <tr>
          <th scope="col" class="th">Permission</th>
          <th scope="col" class="th">Action</th>

        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">

        @foreach ($permissions as $key => $value)
        <tr>
          <td class="td">{{$value->name}}</td>
          <td class="td">
            @component('components.checkbox-toggle',[

            'wireModel' => 'grant',
            'id' => $value->id,
            'index' => $value->id,
            'value' => $value->id,
            ])

            @endcomponent
          </td>
        </tr>
        @endforeach


      </tbody>
    </table>
  </div>

  <div class="flex justify-end">
    @component('components.button-component',[
    'label' => 'Submit',
    'id' => 'submit',
    'type' => 'buttonSmall',
    'wireClickFn' => 'save'
    ])

    @endcomponent
  </div>


</div>
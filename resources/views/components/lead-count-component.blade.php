<h2 class=" hidden sm:flex space-x-[10px] lg:space-x-[16px] xl:space-x-[25px] xl:px-10">
    <div class="header-camp-sub">TOTAL LEADS<span class="text-[#00A5FF]"> : {{ Session::get('totalLeads') }}</span> </div>

    @if (isset($distributionType) && $distributionType == 0)
        <div class="header-camp-sub">ASSIGNED LEADS  <span class="text-[#38C976]"> : {{ Session::get('assignedLeads') }}</span></div>
        <div class="header-camp-sub">UNASSIGNED LEADS <span class="text-[#FF8206]"> : {{ Session::get('totalLeads') - Session::get('assignedLeads') }}</span></div>
    @endif
   
</h2>
<div class="w-64">
    <label for="{{ $wireModel }}" class="block mb-2 text-sm font-normal text-gray-700">{{ $label ?? '' }}
        @if (isset($star) && $star == true)
            <span class="">*</span>
        @endif
    </label>
    <div class="relative" x-data="testDatePicker({{ json_encode($peakSeasonDates ?? []) }}, {{ json_encode($offDaysDates ?? []) }}, '{{ $wireModel }}')" x-init="init()">
        <!-- Hidden input for Livewire binding -->
        <input type="hidden" wire:model{{ isset($live) && $live ? '.live' : '.defer' }}="{{ $wireModel }}"
            x-ref="livewireInput">

        <div class="relative">
            <input type="text" name="{{ $wireModel }}_display" id="{{ $id }}" placeholder="dd/mm/yyyy"
                autocomplete="off" readonly x-ref="input" @click="toggleCalendar()" x-model="displayDate"
                class="form-input !bg-white bg-white !w-full @if (isset($class)) {{ $class }} @endif cursor-pointer"
                style="background-color: white !important; width:70% !important;" required>
            <button type="button" @click="toggleCalendar()"
                class="absolute right-20 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Calendar Dropdown -->
        <div x-show="isOpen" @click.away="closeCalendar()" x-cloak
            class="absolute z-50 mt-2 bg-white border border-gray-300 rounded-lg shadow-lg p-4 w-64"
            style="display: none;">
            <!-- Calendar Header -->
            <div class="flex items-center justify-between mb-4">
                <button @click="previousMonth()" class="p-1 hover:bg-gray-100 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                        </path>
                    </svg>
                </button>
                <h3 class="text-lg font-semibold text-gray-900" x-text="monthNames[currentMonth] + ' ' + currentYear">
                </h3>
                <button @click="nextMonth()" class="p-1 hover:bg-gray-100 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Day Names -->
            <div class="grid grid-cols-7 gap-1 mb-2">
                <template x-for="day in dayNames" :key="day">
                    <div class="text-center text-xs font-normal text-gray-600 py-2" x-text="day"></div>
                </template>
            </div>

            <!-- Calendar Days -->
            <div class="grid grid-cols-7 gap-1">
                <template x-for="day in calendarDays" :key="day.uniqueKey">
                    <button type="button" @click="selectDate(day.date)" :disabled="day.disabled"
                        :class="{
                            'bg-blue-600 text-white': day.selected,
                            'bg-violet-100 text-violet-800 border-violet-300': day.isOffDay && !day.selected && !day
                                .isPeakSeason,
                            'bg-orange-100 text-orange-800 border-orange-300': day.isPeakSeason && !day.selected && !day
                                .isOffDay,
                            'bg-gray-100 text-gray-400 cursor-not-allowed': day.disabled && !day.isPeakSeason && !day
                                .isOffDay,
                            'hover:bg-gray-100': !day.disabled && !day.selected && !day.isPeakSeason && !day.isOffDay,
                            'text-gray-900': !day.disabled && !day.selected && !day.isPeakSeason && !day.isOffDay,
                        }"
                        class="w-8 h-8 rounded-md text-xs font-normal border transition-colors"
                        :title="day.isOffDay ? 'Off Day' : (day.isPeakSeason ? 'Peak Season' : '')">
                        <span x-text="day.day"></span>
                    </button>
                </template>
            </div>
        </div>

        @if (isset($error))
            <span id="error_{{ $wireModel }}" class="error-message">{{ $error }}</span>
        @endif
    </div>
</div>

<script>
    function testDatePicker(peakSeasonDates, offDaysDates, wireModel) {
        return {
            isOpen: false,
            currentDate: new Date(),
            currentMonth: new Date().getMonth(),
            currentYear: new Date().getFullYear(),
            selectedDate: null,
            displayDate: null,
            peakSeasonDates: peakSeasonDates || [],
            offDaysDates: offDaysDates || [],
            dayNames: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December'
            ],
            minDate: '{{ isset($min) ? $min : date('Y-m-d') }}',
            calendarDaysCache: [],

            init() {
                // Initialize calendar days first
                this.updateCalendarDays();

                // Initialize selected date from Livewire
                const wireModelName = '{{ $wireModel }}';

                // Watch for selectedDate changes to update calendar
                this.$watch('selectedDate', () => {
                    this.updateCalendarDays();
                    // Update display date when selected date changes
                    if (this.selectedDate) {
                        this.displayDate = this.formatDateForDisplay(this.selectedDate);
                    } else {
                        this.displayDate = null;
                    }
                });

                // Initialize from current Livewire value
                this.$nextTick(() => {
                    try {
                        // Check hidden input first (most reliable)
                        if (this.$refs.livewireInput && this.$refs.livewireInput.value) {
                            const hiddenValue = this.$refs.livewireInput.value;
                            if (hiddenValue) {
                                this.selectedDate = hiddenValue;
                                this.displayDate = this.formatDateForDisplay(hiddenValue);
                            }
                        }

                        // Also try to get from $wire if available
                        if (this.$wire && typeof this.$wire.get === 'function') {
                            try {
                                const currentValue = this.$wire.get(wireModelName);
                                if (currentValue && currentValue !== this.selectedDate) {
                                    this.selectedDate = currentValue;
                                    this.displayDate = this.formatDateForDisplay(currentValue);
                                }
                            } catch (e) {
                                // Ignore errors if $wire.get is not available
                            }
                        }
                    } catch (e) {
                        console.warn('Error initializing date picker:', e);
                    }
                });
            },

            toggleCalendar() {
                this.isOpen = !this.isOpen;
            },

            closeCalendar() {
                this.isOpen = false;
            },

            previousMonth() {
                if (this.currentMonth === 0) {
                    this.currentMonth = 11;
                    this.currentYear--;
                } else {
                    this.currentMonth--;
                }
                this.updateCalendarDays();
            },

            nextMonth() {
                if (this.currentMonth === 11) {
                    this.currentMonth = 0;
                    this.currentYear++;
                } else {
                    this.currentMonth++;
                }
                this.updateCalendarDays();
            },

            updateCalendarDays() {
                this.calendarDaysCache = this.getCalendarDays();
            },

            getCalendarDays() {
                const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
                const daysInMonth = lastDay.getDate();
                const startingDayOfWeek = firstDay.getDay();

                const days = [];
                const minDateObj = new Date(this.minDate + 'T00:00:00'); // Add time to avoid timezone issues

                // Add empty cells for days before the first day of the month
                for (let i = 0; i < startingDayOfWeek; i++) {
                    days.push({
                        day: '',
                        date: null,
                        disabled: true,
                        selected: false,
                        isPeakSeason: false,
                        isOffDay: false,
                        uniqueKey: `empty-${this.currentYear}-${this.currentMonth}-${i}`
                    });
                }

                // Add days of the month
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(this.currentYear, this.currentMonth, day);
                    const dateStr = this.formatDate(date);
                    const isSelected = this.selectedDate === dateStr;
                    const dateObj = new Date(dateStr + 'T00:00:00');
                    const isDisabled = dateObj < minDateObj;
                    const isPeakSeason = this.isPeakSeasonDate(dateStr);
                    const isOffDay = this.isOffDayDate(dateStr);

                    days.push({
                        day: day,
                        date: dateStr,
                        disabled: isDisabled,
                        selected: isSelected,
                        isPeakSeason: isPeakSeason,
                        isOffDay: isOffDay,
                        uniqueKey: `day-${this.currentYear}-${this.currentMonth}-${day}`
                    });
                }

                return days;
            },

            get calendarDays() {
                // Return cached calendar days, which will be updated when month changes
                return this.calendarDaysCache || [];
            },

            isPeakSeasonDate(dateStr) {
                if (!this.peakSeasonDates || this.peakSeasonDates.length === 0) {
                    return false;
                }

                // Compare date strings directly (YYYY-MM-DD format)
                return this.peakSeasonDates.some(peakSeason => {
                    return dateStr >= peakSeason.start_date && dateStr <= peakSeason.end_date;
                });
            },

            isOffDayDate(dateStr) {
                if (!this.offDaysDates || this.offDaysDates.length === 0) {
                    return false;
                }

                // Compare date strings directly (YYYY-MM-DD format)
                return this.offDaysDates.some(offDay => {
                    return dateStr >= offDay.start_date && dateStr <= offDay.end_date;
                });
            },

            formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },

            formatDateForDisplay(dateStr) {
                // Convert YYYY-MM-DD to dd/mm/yyyy
                if (!dateStr) return '';
                const parts = dateStr.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                return dateStr;
            },

            selectDate(date) {
                if (date) {
                    this.selectedDate = date; // Store in YYYY-MM-DD format
                    this.displayDate = this.formatDateForDisplay(date); // Display in dd/mm/yyyy format
                    // Update calendar to reflect selected date
                    this.updateCalendarDays();
                    // Update hidden input to trigger Livewire update (keep YYYY-MM-DD format)
                    if (this.$refs.livewireInput) {
                        this.$refs.livewireInput.value = date;
                        // Trigger Livewire update by dispatching input event
                        const event = new Event('input', {
                            bubbles: true
                        });
                        this.$refs.livewireInput.dispatchEvent(event);
                    }
                    this.closeCalendar();
                }
            }
        }
    }
</script>

import { Calendar } from '@fullcalendar/core';
import esLocale from '@fullcalendar/core/locales/es';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('reservationCalendar', () => ({
        calendar: null,

        init() {
            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [dayGridPlugin, interactionPlugin],
                initialView: 'dayGridMonth',
                locale: esLocale,
                firstDay: 1,
                height: 'auto',
                dayMaxEvents: true,
                headerToolbar: {
                    start: 'prev,next today',
                    center: 'title',
                    end: '',
                },
                events: async (info, successCallback, failureCallback) => {
                    try {
                        successCallback(await this.$wire.events(info.startStr, info.endStr));
                    } catch (error) {
                        failureCallback(error);
                    }
                },
            });

            this.calendar.render();
        },

        destroy() {
            this.calendar?.destroy();
        },
    }));
});

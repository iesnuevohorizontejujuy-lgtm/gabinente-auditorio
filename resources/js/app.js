import { Calendar } from '@fullcalendar/core';
import esLocale from '@fullcalendar/core/locales/es';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('reservationCalendar', () => ({
        calendar: null,

        init() {
            const canCreate = this.$el.dataset.canCreate === 'true';

            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
                initialView: 'timeGridWeek',
                locale: esLocale,
                firstDay: 1,
                hiddenDays: [0],
                height: 'auto',
                expandRows: true,
                nowIndicator: true,
                allDaySlot: false,
                slotMinTime: '08:00:00',
                slotMaxTime: '21:00:00',
                slotDuration: '00:20:00',
                slotLabelInterval: '01:00:00',
                selectable: canCreate,
                selectMirror: true,
                selectOverlap: false,
                eventOverlap: false,
                dayMaxEvents: true,
                headerToolbar: {
                    start: 'prev,next today',
                    center: 'title',
                    end: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
                events: async (info, successCallback, failureCallback) => {
                    try {
                        successCallback(await this.$wire.events(info.startStr, info.endStr));
                    } catch (error) {
                        failureCallback(error);
                    }
                },
                select: async (info) => {
                    if (!canCreate || info.allDay) {
                        this.calendar.unselect();

                        return;
                    }

                    await this.$wire.prepareCreateFromCalendar(info.startStr, info.endStr);
                    this.calendar.unselect();
                },
                eventDidMount: (info) => {
                    const { sala, profesor } = info.event.extendedProps;
                    info.el.title = `${info.event.title} · ${sala} · Prof. ${profesor}`;
                },
            });

            this.calendar.render();
        },

        destroy() {
            this.calendar?.destroy();
        },
    }));
});

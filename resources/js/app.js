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
                allDaySlot: true,
                allDayText: 'Feriados',
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
                eventClick: async (info) => {
                    const { kind, canEdit, canCancel } = info.event.extendedProps;

                    if (kind === 'reservation' && (canEdit || canCancel)) {
                        await this.$wire.openReservation(Number(info.event.id));
                    }
                },
                eventContent: (info) => {
                    const { kind, profesor, carreras = [], jurisdiccion } = info.event.extendedProps;
                    const content = document.createElement('div');

                    if (kind === 'holiday') {
                        content.className = 'calendar-holiday-content';

                        const marker = document.createElement('span');
                        marker.className = 'calendar-holiday-marker';
                        marker.setAttribute('aria-hidden', 'true');

                        const title = document.createElement('span');
                        title.className = 'calendar-holiday-title';
                        title.textContent = info.event.title;

                        const scope = document.createElement('span');
                        scope.className = 'calendar-holiday-scope';
                        scope.textContent = jurisdiccion === 'jujuy' ? 'Jujuy' : 'Nacional';

                        content.append(marker, title, scope);

                        return { domNodes: [content] };
                    }

                    content.className = 'calendar-event-content';

                    if (info.timeText) {
                        const time = document.createElement('span');
                        time.className = 'calendar-event-time';
                        time.textContent = info.timeText;
                        content.append(time);
                    }

                    const title = document.createElement('span');
                    title.className = 'calendar-event-title';
                    title.textContent = info.event.title;
                    content.append(title);

                    const owner = document.createElement('span');
                    owner.className = 'calendar-event-owner';
                    owner.textContent = `Profesor/a ${profesor}`;
                    content.append(owner);

                    const career = document.createElement('span');
                    career.className = 'calendar-event-careers';
                    career.textContent = carreras.length > 0 ? carreras.join(' · ') : 'Sin carrera informada';
                    content.append(career);

                    return { domNodes: [content] };
                },
                eventDidMount: (info) => {
                    const { kind, sala, profesor, carreras = [], estado, jurisdiccion, tipo, canEdit, canCancel } = info.event.extendedProps;

                    info.el.title = kind === 'holiday'
                        ? `Feriado ${jurisdiccion}: ${info.event.title}${tipo ? ` · ${tipo}` : ''}`
                        : `${estado} · ${info.event.title} · ${sala} · Profesor/a ${profesor} · ${carreras.join(' · ') || 'Sin carrera informada'}`;

                    if (canEdit || canCancel) {
                        info.el.classList.add('calendar-event-actionable');
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

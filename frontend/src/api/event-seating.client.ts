import {api} from './client';
import {EventSeatingSettings, EventTablePosition, GenericDataResponse, IdParam} from '../types';

export const eventSeatingClient = {
    get: async (eventId: IdParam) => {
        const response = await api.get<GenericDataResponse<EventSeatingSettings>>(`events/${eventId}/seating`);
        return response.data;
    },
    update: async (eventId: IdParam, settings: Pick<EventSeatingSettings, 'table_count' | 'seats_per_table'>) => {
        const response = await api.put<GenericDataResponse<EventSeatingSettings>>(
            `events/${eventId}/seating`,
            settings,
        );
        return response.data;
    },
    updateLayout: async (
        eventId: IdParam,
        blueprintImageId: IdParam | null,
        tablePositions: EventTablePosition[],
    ) => {
        const response = await api.put<GenericDataResponse<EventSeatingSettings>>(
            `events/${eventId}/seating/layout`,
            {blueprint_image_id: blueprintImageId, table_positions: tablePositions},
        );
        return response.data;
    },
    assignSeat: async (
        eventId: IdParam,
        attendeeId: IdParam,
        tableNumber: number | null,
        seatNumber: number | null,
    ) => {
        const response = await api.put<GenericDataResponse<EventSeatingSettings>>(
            `events/${eventId}/seating/assignments/${attendeeId}`,
            {table_number: tableNumber, seat_number: seatNumber},
        );
        return response.data;
    },
};

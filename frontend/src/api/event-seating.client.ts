import {api} from './client';
import {EventSeatingSettings, GenericDataResponse, IdParam} from '../types';

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
};

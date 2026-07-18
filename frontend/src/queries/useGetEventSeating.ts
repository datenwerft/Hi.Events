import {useQuery} from '@tanstack/react-query';
import {eventSeatingClient} from '../api/event-seating.client';
import {IdParam} from '../types';

export const GET_EVENT_SEATING_QUERY_KEY = 'getEventSeating';

export const useGetEventSeating = (eventId: IdParam) => useQuery({
    queryKey: [GET_EVENT_SEATING_QUERY_KEY, eventId],
    queryFn: async () => (await eventSeatingClient.get(eventId)).data,
    enabled: !!eventId,
});

import {useMutation, useQueryClient} from '@tanstack/react-query';
import {eventSeatingClient} from '../api/event-seating.client';
import {IdParam} from '../types';
import {GET_EVENT_SEATING_QUERY_KEY} from '../queries/useGetEventSeating';

export const useAssignEventSeat = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, attendeeId, tableNumber, seatNumber}: {
            eventId: IdParam;
            attendeeId: IdParam;
            tableNumber: number | null;
            seatNumber: number | null;
        }) => eventSeatingClient.assignSeat(eventId, attendeeId, tableNumber, seatNumber),
        onSettled: (_data, _error, variables) => queryClient.invalidateQueries({
            queryKey: [GET_EVENT_SEATING_QUERY_KEY, variables.eventId],
        }),
    });
};

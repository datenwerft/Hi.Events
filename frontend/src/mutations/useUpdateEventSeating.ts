import {useMutation, useQueryClient} from '@tanstack/react-query';
import {eventSeatingClient} from '../api/event-seating.client';
import {EventTableType, IdParam} from '../types';
import {GET_EVENT_SEATING_QUERY_KEY} from '../queries/useGetEventSeating';

export const useUpdateEventSeating = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, settings}: {
            eventId: IdParam;
            settings: {table_types: EventTableType[]};
        }) => eventSeatingClient.update(eventId, settings),
        onSuccess: (_data, variables) => queryClient.invalidateQueries({
            queryKey: [GET_EVENT_SEATING_QUERY_KEY, variables.eventId],
        }),
    });
};

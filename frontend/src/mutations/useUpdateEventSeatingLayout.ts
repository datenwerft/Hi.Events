import {useMutation, useQueryClient} from '@tanstack/react-query';
import {eventSeatingClient} from '../api/event-seating.client';
import {EventTablePosition, IdParam} from '../types';
import {GET_EVENT_SEATING_QUERY_KEY} from '../queries/useGetEventSeating';

export const useUpdateEventSeatingLayout = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, blueprintImageId, tablePositions}: {
            eventId: IdParam;
            blueprintImageId: IdParam | null;
            tablePositions: EventTablePosition[];
        }) => eventSeatingClient.updateLayout(eventId, blueprintImageId, tablePositions),
        onSettled: (_data, _error, variables) => queryClient.invalidateQueries({
            queryKey: [GET_EVENT_SEATING_QUERY_KEY, variables.eventId],
        }),
    });
};

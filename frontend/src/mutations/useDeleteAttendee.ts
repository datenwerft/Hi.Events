import {useMutation, useQueryClient} from "@tanstack/react-query";
import {IdParam} from "../types.ts";
import {attendeesClient} from "../api/attendee.client.ts";
import {GET_ATTENDEES_QUERY_KEY} from "../queries/useGetAttendees.ts";
import {GET_ORDER_QUERY_KEY} from "../queries/useGetOrder.ts";

export const useDeleteAttendee = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, attendeeId}: {
            eventId: IdParam,
            attendeeId: IdParam,
        }) => attendeesClient.delete(eventId, attendeeId),

        onSuccess: () => Promise.all([
            queryClient.invalidateQueries({queryKey: [GET_ATTENDEES_QUERY_KEY]}),
            queryClient.invalidateQueries({queryKey: [GET_ORDER_QUERY_KEY]}),
        ]),
    });
};

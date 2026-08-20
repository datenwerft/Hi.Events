import {useMutation} from "@tanstack/react-query";
import {
    attendeesClient,
    AttendeeQuestionAnswerRequest,
} from "../api/attendee.client.ts";
import {IdParam} from "../types.ts";

export const useUpsertAttendeeQuestionAnswers = () => useMutation({
    mutationFn: ({eventId, attendeeId, questionAnswers}: {
        eventId: IdParam;
        attendeeId: IdParam;
        questionAnswers: AttendeeQuestionAnswerRequest[];
    }) => attendeesClient.upsertQuestionAnswers(eventId, attendeeId, questionAnswers),
});

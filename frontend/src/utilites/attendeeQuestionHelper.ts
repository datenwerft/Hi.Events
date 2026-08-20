import {AttendeeQuestionAnswerRequest} from "../api/attendee.client.ts";
import {IdParam, Question, QuestionAnswer, QuestionBelongsToType, QuestionType} from "../types.ts";

export interface AttendeeQuestionFormValue {
    question_id: IdParam;
    value: Record<string, unknown>;
}

export const getApplicableAttendeeQuestions = (
    questions: Question[] | undefined,
    productId: IdParam | undefined,
): Question[] => {
    if (!questions || !productId) {
        return [];
    }

    return questions.filter(question =>
        question.belongs_to === QuestionBelongsToType.PRODUCT
        && !question.is_hidden
        && question.product_ids?.some(id => String(id) === String(productId))
    );
};

export const buildAttendeeQuestionFormValues = (
    questions: Question[],
    existingAnswers: QuestionAnswer[] = [],
): AttendeeQuestionFormValue[] => questions.map(question => {
    const existingAnswer = existingAnswers.find(answer => answer.question_id === question.id)?.answer;

    if (question.type === QuestionType.ADDRESS) {
        return {
            question_id: question.id as IdParam,
            value: typeof existingAnswer === 'object' && !Array.isArray(existingAnswer)
                ? existingAnswer
                : {},
        };
    }

    return {
        question_id: question.id as IdParam,
        value: {
            answer: existingAnswer ?? (question.type === QuestionType.CHECKBOX ? [] : ''),
        },
    };
});

export const serializeAttendeeQuestionAnswers = (
    values: AttendeeQuestionFormValue[],
    questions: Question[],
): AttendeeQuestionAnswerRequest[] => values.map(value => {
    const question = questions.find(item => String(item.id) === String(value.question_id));

    return {
        question_id: value.question_id,
        answer: question?.type === QuestionType.ADDRESS
            ? value.value
            : value.value.answer,
    };
});

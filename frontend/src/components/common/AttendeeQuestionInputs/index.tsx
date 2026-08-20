import {UseFormReturnType} from "@mantine/form";
import {Question} from "../../../types.ts";
import {QuestionInput} from "../CheckoutQuestion";

interface AttendeeQuestionInputsProps {
    questions: Question[];
    form: UseFormReturnType<any, any>;
}

export const AttendeeQuestionInputs = ({questions, form}: AttendeeQuestionInputsProps) => (
    <>
        {questions.map((question, index) => (
            <QuestionInput
                key={question.id}
                question={question}
                name={`question_answers.${index}.value`}
                form={form}
            />
        ))}
    </>
);

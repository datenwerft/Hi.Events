import {Alert, Group, NumberInput, Text} from '@mantine/core';
import {useForm} from '@mantine/form';
import {t, Trans} from '@lingui/macro';
import {useEffect} from 'react';
import {useParams} from 'react-router';
import {useFormErrorResponseHandler} from '../../../hooks/useFormErrorResponseHandler';
import {useUpdateEventSeating} from '../../../mutations/useUpdateEventSeating';
import {useGetEventSeating} from '../../../queries/useGetEventSeating';
import {GenericModalProps} from '../../../types';
import {showSuccess} from '../../../utilites/notifications';
import {Button} from '../../common/Button';
import {LoadingMask} from '../../common/LoadingMask';
import {Modal} from '../../common/Modal';

export const EventSeatingModal = ({onClose}: GenericModalProps) => {
    const {eventId} = useParams();
    const seatingQuery = useGetEventSeating(eventId);
    const mutation = useUpdateEventSeating();
    const errorHandler = useFormErrorResponseHandler();
    const form = useForm({
        initialValues: {table_count: 0, seats_per_table: 0},
    });

    useEffect(() => {
        if (seatingQuery.data) {
            form.initialize({
                table_count: seatingQuery.data.table_count,
                seats_per_table: seatingQuery.data.seats_per_table,
            });
        }
    }, [seatingQuery.data]);

    if (seatingQuery.isLoading) {
        return <LoadingMask/>;
    }

    const handleSubmit = (values: typeof form.values) => {
        mutation.mutate({eventId, settings: values}, {
            onSuccess: () => {
                showSuccess(t`Seating configuration saved`);
                onClose();
            },
            onError: error => errorHandler(form, error),
        });
    };

    const totalSeats = form.values.table_count * form.values.seats_per_table;

    return (
        <Modal opened onClose={onClose} heading={t`Table & Seat Management`}>
            <form onSubmit={form.onSubmit(handleSubmit)}>
                <Text c="dimmed" size="sm" mb="md">
                    {t`Configure the table layout for this event. Set both values to zero to disable seating.`}
                </Text>
                <Group grow align="start">
                    <NumberInput
                        {...form.getInputProps('table_count')}
                        label={t`Number of tables`}
                        min={0}
                        max={500}
                        allowDecimal={false}
                    />
                    <NumberInput
                        {...form.getInputProps('seats_per_table')}
                        label={t`Seats per table`}
                        min={0}
                        max={500}
                        allowDecimal={false}
                    />
                </Group>
                <Alert mt="md" variant="light">
                    <Trans>
                        {totalSeats} seats total; {seatingQuery.data?.assigned_seats || 0} currently assigned.
                    </Trans>
                </Alert>
                <Button type="submit" fullWidth mt="lg" loading={mutation.isPending}>
                    {t`Save seating configuration`}
                </Button>
            </form>
        </Modal>
    );
};

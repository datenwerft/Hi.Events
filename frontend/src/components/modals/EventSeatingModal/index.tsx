import {ActionIcon, Alert, Group, NumberInput, Paper, Select, SimpleGrid, Stack, Text, TextInput} from '@mantine/core';
import {useForm} from '@mantine/form';
import {t, Trans} from '@lingui/macro';
import {IconPlus, IconTrash} from '@tabler/icons-react';
import {useEffect} from 'react';
import {useParams} from 'react-router';
import {useFormErrorResponseHandler} from '../../../hooks/useFormErrorResponseHandler';
import {useUpdateEventSeating} from '../../../mutations/useUpdateEventSeating';
import {useGetEventSeating} from '../../../queries/useGetEventSeating';
import {EventTableShape, EventTableType, GenericModalProps} from '../../../types';
import {showSuccess} from '../../../utilites/notifications';
import {Button} from '../../common/Button';
import {LoadingMask} from '../../common/LoadingMask';
import {Modal} from '../../common/Modal';

const createTableTypeId = () => `type-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

export const EventSeatingModal = ({onClose}: GenericModalProps) => {
    const {eventId} = useParams();
    const seatingQuery = useGetEventSeating(eventId);
    const mutation = useUpdateEventSeating();
    const errorHandler = useFormErrorResponseHandler();
    const form = useForm<{table_types: EventTableType[]}>(
        {initialValues: {table_types: []}},
    );

    useEffect(() => {
        if (seatingQuery.data) {
            form.initialize({table_types: seatingQuery.data.table_types || []});
        }
        // Mantine keeps the form instance stable; rerun only when the fetched configuration changes.
        // eslint-disable-next-line react-hooks/exhaustive-deps
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

    const addTableType = () => {
        const typeNumber = form.values.table_types.length + 1;
        form.insertListItem('table_types', {
            id: createTableTypeId(),
            name: t`Table type ${typeNumber}`,
            shape: 'round' as EventTableShape,
            table_count: 1,
            seats_per_table: 8,
        });
    };

    const totalTables = form.values.table_types.reduce((sum, type) => sum + (Number(type.table_count) || 0), 0);
    const totalSeats = form.values.table_types.reduce(
        (sum, type) => sum + (Number(type.table_count) || 0) * (Number(type.seats_per_table) || 0),
        0,
    );
    const shapeOptions = [
        {value: 'round', label: t`Round`},
        {value: 'square', label: t`Square`},
        {value: 'rectangle', label: t`Rectangle`},
    ];

    return (
        <Modal opened onClose={onClose} heading={t`Table & Seat Management`}>
            <form onSubmit={form.onSubmit(handleSubmit)}>
                <Text c="dimmed" size="sm" mb="md">
                    {t`Add one or more table types. Each type can have its own quantity, number of seats, and shape.`}
                </Text>

                <Stack gap="md">
                    {form.values.table_types.map((type, index) => (
                        <Paper key={type.id} withBorder radius="md" p="md">
                            <Group justify="space-between" mb="sm">
                                <Text fw={600}>{type.name}</Text>
                                <ActionIcon
                                    type="button"
                                    variant="subtle"
                                    color="red"
                                    aria-label={t`Remove table type`}
                                    onClick={() => form.removeListItem('table_types', index)}
                                >
                                    <IconTrash size={18}/>
                                </ActionIcon>
                            </Group>
                            <TextInput
                                {...form.getInputProps(`table_types.${index}.name`)}
                                label={t`Type name`}
                                placeholder={t`Dinner tables`}
                                mb="sm"
                                required
                            />
                            <SimpleGrid cols={{base: 1, sm: 3}}>
                                <Select
                                    {...form.getInputProps(`table_types.${index}.shape`)}
                                    label={t`Table shape`}
                                    data={shapeOptions}
                                    allowDeselect={false}
                                    required
                                />
                                <NumberInput
                                    {...form.getInputProps(`table_types.${index}.table_count`)}
                                    label={t`Number of tables`}
                                    min={1}
                                    max={500}
                                    allowDecimal={false}
                                    required
                                />
                                <NumberInput
                                    {...form.getInputProps(`table_types.${index}.seats_per_table`)}
                                    label={t`Seats per table`}
                                    min={1}
                                    max={500}
                                    allowDecimal={false}
                                    required
                                />
                            </SimpleGrid>
                        </Paper>
                    ))}
                </Stack>

                {form.errors.table_types && (
                    <Alert mt="md" color="red">{form.errors.table_types}</Alert>
                )}

                <Button
                    type="button"
                    variant="light"
                    mt="md"
                    leftSection={<IconPlus size={18}/>}
                    onClick={addTableType}
                >
                    {t`Add table type`}
                </Button>

                <Alert mt="md" variant="light">
                    {form.values.table_types.length === 0 ? (
                        <Text size="sm">{t`No table types configured. Saving will disable seating.`}</Text>
                    ) : (
                        <Trans>{totalTables} tables and {totalSeats} seats total; {seatingQuery.data?.assigned_seats || 0} currently assigned.</Trans>
                    )}
                </Alert>
                <Button type="submit" fullWidth mt="lg" loading={mutation.isPending}>
                    {t`Save seating configuration`}
                </Button>
            </form>
        </Modal>
    );
};

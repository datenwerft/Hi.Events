import {Alert, Badge, Button, Group, Paper, SimpleGrid, Text, Tooltip} from '@mantine/core';
import {t} from '@lingui/macro';
import {IconArmchair, IconSettings} from '@tabler/icons-react';
import {useMemo, useState} from 'react';
import {useParams} from 'react-router';
import {useDisclosure} from '@mantine/hooks';
import {EventSeatAssignment, IdParam} from '../../../../types';
import {useGetEventSeating} from '../../../../queries/useGetEventSeating';
import {PageBody} from '../../../common/PageBody';
import {PageTitle} from '../../../common/PageTitle';
import {ToolBar} from '../../../common/ToolBar';
import {LoadingMask} from '../../../common/LoadingMask';
import {EventSeatingModal} from '../../../modals/EventSeatingModal';
import {ManageAttendeeModal} from '../../../modals/ManageAttendeeModal';
import classes from './TablesAndSeats.module.scss';

interface TableGraphicProps {
    tableNumber: number;
    seatsPerTable: number;
    assignments: Map<number, EventSeatAssignment>;
    onSelectAttendee: (attendeeId: IdParam) => void;
}

const TableGraphic = ({
    tableNumber,
    seatsPerTable,
    assignments,
    onSelectAttendee,
}: TableGraphicProps) => {
    const occupiedSeats = assignments.size;

    return (
        <Paper className={classes.tableCard} withBorder radius="md">
            <div className={classes.tableGraphic}>
                <div className={classes.tableTop}>
                    <Text fw={700}>{t`Table ${tableNumber}`}</Text>
                    <Text size="xs" c="dimmed">
                        {occupiedSeats}/{seatsPerTable} {t`occupied`}
                    </Text>
                </div>

                {Array.from({length: seatsPerTable}, (_, index) => {
                    const seatNumber = index + 1;
                    const assignment = assignments.get(seatNumber);
                    const angle = ((Math.PI * 2) / seatsPerTable) * index - Math.PI / 2;
                    const left = 50 + Math.cos(angle) * 43;
                    const top = 50 + Math.sin(angle) * 43;
                    const attendeeName = assignment
                        ? `${assignment.first_name} ${assignment.last_name}`
                        : t`Open seat`;
                    const tooltip = assignment
                        ? `${attendeeName} · ${assignment.email}`
                        : t`Seat ${seatNumber} is open`;

                    return (
                        <Tooltip key={seatNumber} label={tooltip} withArrow>
                            <button
                                type="button"
                                className={`${classes.seat} ${assignment ? classes.occupied : classes.open}`}
                                style={{left: `${left}%`, top: `${top}%`}}
                                aria-label={assignment
                                    ? t`Seat ${seatNumber}, occupied by ${attendeeName}`
                                    : t`Seat ${seatNumber}, open`}
                                onClick={() => assignment && onSelectAttendee(assignment.attendee_id)}
                                aria-disabled={!assignment}
                            >
                                {seatNumber}
                            </button>
                        </Tooltip>
                    );
                })}
            </div>
        </Paper>
    );
};

const TablesAndSeats = () => {
    const {eventId} = useParams();
    const seatingQuery = useGetEventSeating(eventId);
    const seating = seatingQuery.data;
    const [configurationOpen, configurationModal] = useDisclosure(false);
    const [selectedAttendeeId, setSelectedAttendeeId] = useState<IdParam>();

    const assignmentsByTable = useMemo(() => {
        const byTable = new Map<number, Map<number, EventSeatAssignment>>();

        seating?.assignments?.forEach(assignment => {
            if (!byTable.has(assignment.table_number)) {
                byTable.set(assignment.table_number, new Map());
            }
            byTable.get(assignment.table_number)?.set(assignment.seat_number, assignment);
        });

        return byTable;
    }, [seating?.assignments]);

    if (seatingQuery.isLoading) {
        return <LoadingMask/>;
    }

    const isConfigured = !!seating && seating.table_count > 0 && seating.seats_per_table > 0;
    const availableSeats = (seating?.total_seats || 0) - (seating?.assigned_seats || 0);

    return (
        <>
            <PageBody>
                <PageTitle subheading={t`See every table and which seats are occupied or available.`}>
                    {t`Tables & Seats`}
                </PageTitle>

                <ToolBar>
                    <Button
                        color="green"
                        size="sm"
                        onClick={configurationModal.open}
                        rightSection={<IconSettings size={18}/>}
                    >
                        {t`Configure tables`}
                    </Button>
                </ToolBar>

                {!isConfigured ? (
                    <Alert
                        icon={<IconArmchair/>}
                        title={t`No tables configured`}
                        color="blue"
                        mt="lg"
                    >
                        <Text size="sm" mb="md">
                            {t`Configure the number of tables and seats per table to create the seating view.`}
                        </Text>
                        <Button variant="light" color="blue" onClick={configurationModal.open}>
                            {t`Configure tables`}
                        </Button>
                    </Alert>
                ) : (
                    <>
                        <Paper className={classes.summary} withBorder radius="md">
                            <Group justify="space-between" gap="md">
                                <Group gap="xs">
                                    <Badge size="lg" variant="light">
                                        {seating.table_count} {t`tables`}
                                    </Badge>
                                    <Badge size="lg" variant="light" color="red">
                                        {seating.assigned_seats} {t`occupied`}
                                    </Badge>
                                    <Badge size="lg" variant="light" color="green">
                                        {availableSeats} {t`open`}
                                    </Badge>
                                </Group>
                                <Group gap="lg" className={classes.legend}>
                                    <span><i className={classes.occupiedSwatch}/>{t`Occupied`}</span>
                                    <span><i className={classes.openSwatch}/>{t`Open`}</span>
                                </Group>
                            </Group>
                        </Paper>

                        <SimpleGrid className={classes.tableGrid} cols={{base: 1, sm: 2, lg: 3, xl: 4}}>
                            {Array.from({length: seating.table_count}, (_, index) => {
                                const tableNumber = index + 1;
                                return (
                                    <TableGraphic
                                        key={tableNumber}
                                        tableNumber={tableNumber}
                                        seatsPerTable={seating.seats_per_table}
                                        assignments={assignmentsByTable.get(tableNumber) || new Map()}
                                        onSelectAttendee={setSelectedAttendeeId}
                                    />
                                );
                            })}
                        </SimpleGrid>
                    </>
                )}
            </PageBody>

            {configurationOpen && (
                <EventSeatingModal isOpen onClose={configurationModal.close}/>
            )}
            {selectedAttendeeId && (
                <ManageAttendeeModal
                    isOpen
                    attendeeId={selectedAttendeeId}
                    onClose={() => setSelectedAttendeeId(undefined)}
                />
            )}
        </>
    );
};

export default TablesAndSeats;

import {
    Alert,
    Badge,
    Button,
    FileButton,
    Group,
    Modal,
    Paper,
    Select,
    SegmentedControl,
    SimpleGrid,
    Slider,
    Stack,
    Text,
    Tooltip,
} from '@mantine/core';
import {t} from '@lingui/macro';
import {IconArmchair, IconPhoto, IconSettings, IconTrash, IconZoomIn} from '@tabler/icons-react';
import {PointerEvent as ReactPointerEvent, useEffect, useMemo, useRef, useState} from 'react';
import {useParams} from 'react-router';
import {useDisclosure} from '@mantine/hooks';
import {EventSeatAssignment, EventTablePosition, IdParam} from '../../../../types';
import {useGetEventSeating} from '../../../../queries/useGetEventSeating';
import {useUpdateEventSeatingLayout} from '../../../../mutations/useUpdateEventSeatingLayout';
import {useAssignEventSeat} from '../../../../mutations/useAssignEventSeat';
import {useUploadImage} from '../../../../mutations/useUploadImage';
import {imageClient} from '../../../../api/image.client';
import {showError, showSuccess} from '../../../../utilites/notifications';
import {PageBody} from '../../../common/PageBody';
import {PageTitle} from '../../../common/PageTitle';
import {ToolBar} from '../../../common/ToolBar';
import {LoadingMask} from '../../../common/LoadingMask';
import {EventSeatingModal} from '../../../modals/EventSeatingModal';
import {ManageAttendeeModal} from '../../../modals/ManageAttendeeModal';
import classes from './TablesAndSeats.module.scss';

interface SelectedSeat {
    tableNumber: number;
    seatNumber: number;
    assignment?: EventSeatAssignment;
}

interface TableGraphicProps {
    tableNumber: number;
    seatsPerTable: number;
    assignments: Map<number, EventSeatAssignment>;
    onSelectSeat: (seat: SelectedSeat) => void;
}

const TableGraphic = ({tableNumber, seatsPerTable, assignments, onSelectSeat}: TableGraphicProps) => (
    <Paper className={classes.tableCard} withBorder radius="md">
        <div className={classes.tableGraphic}>
            <div className={classes.tableTop}>
                <Text fw={700}>{t`Table ${tableNumber}`}</Text>
                <Text size="xs" c="dimmed">{assignments.size}/{seatsPerTable} {t`occupied`}</Text>
            </div>
            {Array.from({length: seatsPerTable}, (_, index) => {
                const seatNumber = index + 1;
                const assignment = assignments.get(seatNumber);
                const angle = ((Math.PI * 2) / seatsPerTable) * index - Math.PI / 2;
                const attendeeName = assignment ? `${assignment.first_name} ${assignment.last_name}` : t`Open seat`;
                return (
                    <Tooltip key={seatNumber} label={assignment ? `${attendeeName} · ${assignment.email}` : t`Click to assign seat ${seatNumber}`} withArrow>
                        <button
                            type="button"
                            className={`${classes.seat} ${assignment ? classes.occupied : classes.open}`}
                            style={{left: `${50 + Math.cos(angle) * 43}%`, top: `${50 + Math.sin(angle) * 43}%`}}
                            aria-label={assignment ? t`Seat ${seatNumber}, occupied by ${attendeeName}` : t`Seat ${seatNumber}, open`}
                            onClick={() => onSelectSeat({tableNumber, seatNumber, assignment})}
                        >
                            {seatNumber}
                        </button>
                    </Tooltip>
                );
            })}
        </div>
    </Paper>
);

const TablesAndSeats = () => {
    const {eventId} = useParams();
    const seatingQuery = useGetEventSeating(eventId);
    const seating = seatingQuery.data;
    const [configurationOpen, configurationModal] = useDisclosure(false);
    const [selectedSeat, setSelectedSeat] = useState<SelectedSeat>();
    const [selectedAttendeeId, setSelectedAttendeeId] = useState<IdParam>();
    const [attendeeToAssign, setAttendeeToAssign] = useState<string | null>(null);
    const [positions, setPositions] = useState<EventTablePosition[]>([]);
    const [draggingTable, setDraggingTable] = useState<number>();
    const [zoom, setZoom] = useState(100);
    const [blueprintBusy, setBlueprintBusy] = useState(false);
    const [viewMode, setViewMode] = useState<'blueprint' | 'tables'>('blueprint');
    const canvasRef = useRef<HTMLDivElement>(null);
    const layoutMutation = useUpdateEventSeatingLayout();
    const assignmentMutation = useAssignEventSeat();
    const uploadMutation = useUploadImage();

    const assignmentsByTable = useMemo(() => {
        const byTable = new Map<number, Map<number, EventSeatAssignment>>();
        seating?.assignments?.forEach(assignment => {
            if (!byTable.has(assignment.table_number)) byTable.set(assignment.table_number, new Map());
            byTable.get(assignment.table_number)?.set(assignment.seat_number, assignment);
        });
        return byTable;
    }, [seating?.assignments]);

    useEffect(() => {
        if (!seating?.table_count) return;
        const saved = new Map((seating.table_positions || []).map(position => [position.table_number, position]));
        setPositions(Array.from({length: seating.table_count}, (_, index) => {
            const tableNumber = index + 1;
            const columns = Math.ceil(Math.sqrt(seating.table_count));
            const rows = Math.ceil(seating.table_count / columns);
            return saved.get(tableNumber) || {
                table_number: tableNumber,
                x: ((index % columns) + 0.5) * (100 / columns),
                y: (Math.floor(index / columns) + 0.5) * (100 / rows),
            };
        }));
    }, [seating?.table_count, seating?.table_positions]);

    useEffect(() => {
        if (!draggingTable) return;
        const handleMove = (event: PointerEvent) => {
            const rect = canvasRef.current?.getBoundingClientRect();
            if (!rect) return;
            const x = Math.max(5, Math.min(95, ((event.clientX - rect.left) / rect.width) * 100));
            const y = Math.max(5, Math.min(95, ((event.clientY - rect.top) / rect.height) * 100));
            setPositions(current => current.map(position => position.table_number === draggingTable
                ? {...position, x, y}
                : position));
        };
        const handleUp = () => setDraggingTable(undefined);
        window.addEventListener('pointermove', handleMove);
        window.addEventListener('pointerup', handleUp, {once: true});
        return () => {
            window.removeEventListener('pointermove', handleMove);
            window.removeEventListener('pointerup', handleUp);
        };
    }, [draggingTable]);

    if (seatingQuery.isLoading) return <LoadingMask/>;

    const isConfigured = !!seating && seating.table_count > 0 && seating.seats_per_table > 0;
    const availableSeats = (seating?.total_seats || 0) - (seating?.assigned_seats || 0);
    const attendeeOptions = (seating?.available_attendees || []).map(attendee => ({
        value: String(attendee.attendee_id),
        label: `${attendee.last_name}, ${attendee.first_name} · ${attendee.email}`,
    }));
    const selectedTableNumber = selectedSeat?.tableNumber;
    const selectedSeatNumber = selectedSeat?.seatNumber;

    const saveLayout = (blueprintImageId: IdParam | null = seating?.blueprint?.id || null) => {
        if (!eventId) return;
        layoutMutation.mutate({eventId, blueprintImageId, tablePositions: positions}, {
            onSuccess: () => showSuccess(t`Room plan saved`),
            onError: () => showError(t`Could not save the room plan`),
        });
    };

    const uploadBlueprint = (file: File | null) => {
        if (!file || !eventId) return;
        setBlueprintBusy(true);
        uploadMutation.mutate({image: file, imageType: 'ROOM_BLUEPRINT', entityId: eventId}, {
            onSuccess: response => {
                const imageId = response.data.id;
                layoutMutation.mutate({eventId, blueprintImageId: imageId, tablePositions: positions}, {
                    onSuccess: () => showSuccess(t`Room blueprint uploaded`),
                    onError: () => showError(t`The blueprint uploaded, but could not be attached to the room plan`),
                    onSettled: () => setBlueprintBusy(false),
                });
            },
            onError: () => {
                showError(t`Could not upload the room blueprint`);
                setBlueprintBusy(false);
            },
        });
    };

    const removeBlueprint = async () => {
        if (!eventId || !seating?.blueprint) return;
        setBlueprintBusy(true);
        try {
            await layoutMutation.mutateAsync({eventId, blueprintImageId: null, tablePositions: positions});
            await imageClient.delete(seating.blueprint.id);
            showSuccess(t`Room blueprint removed`);
        } catch {
            showError(t`Could not remove the room blueprint`);
        } finally {
            setBlueprintBusy(false);
        }
    };

    const assignSelectedSeat = () => {
        if (!eventId || !selectedSeat || !attendeeToAssign) return;
        assignmentMutation.mutate({
            eventId,
            attendeeId: attendeeToAssign,
            tableNumber: selectedSeat.tableNumber,
            seatNumber: selectedSeat.seatNumber,
        }, {
            onSuccess: () => {
                showSuccess(t`Attendee assigned to seat`);
                setSelectedSeat(undefined);
                setAttendeeToAssign(null);
            },
            onError: () => showError(t`Could not assign the attendee to this seat`),
        });
    };

    const removeAssignment = () => {
        if (!eventId || !selectedSeat?.assignment) return;
        assignmentMutation.mutate({
            eventId,
            attendeeId: selectedSeat.assignment.attendee_id,
            tableNumber: null,
            seatNumber: null,
        }, {
            onSuccess: () => {
                showSuccess(t`Seat assignment removed`);
                setSelectedSeat(undefined);
            },
            onError: () => showError(t`Could not remove the seat assignment`),
        });
    };

    return (
        <>
            <PageBody>
                <PageTitle subheading={t`Place tables on the room blueprint and assign attendees directly to chairs.`}>
                    {t`Tables & Seats`}
                </PageTitle>
                <ToolBar>
                    <Group gap="sm">
                        {isConfigured && (
                            <FileButton onChange={uploadBlueprint} accept="image/png,image/jpeg,image/webp">
                                {props => <Button {...props} variant="light" loading={blueprintBusy} leftSection={<IconPhoto size={18}/>}>{seating?.blueprint ? t`Replace blueprint` : t`Upload blueprint`}</Button>}
                            </FileButton>
                        )}
                        <Button color="green" size="sm" onClick={configurationModal.open} rightSection={<IconSettings size={18}/>}>{t`Configure tables`}</Button>
                    </Group>
                </ToolBar>

                {!isConfigured ? (
                    <Alert icon={<IconArmchair/>} title={t`No tables configured`} color="blue" mt="lg">
                        <Text size="sm" mb="md">{t`Configure the number of tables and seats per table to create the seating view.`}</Text>
                        <Button variant="light" color="blue" onClick={configurationModal.open}>{t`Configure tables`}</Button>
                    </Alert>
                ) : (
                    <>
                        <Paper className={classes.summary} withBorder radius="md">
                            <Group justify="space-between" gap="md">
                                <Group gap="xs">
                                    <Badge size="lg" variant="light">{seating.table_count} {t`tables`}</Badge>
                                    <Badge size="lg" variant="light" color="red">{seating.assigned_seats} {t`occupied`}</Badge>
                                    <Badge size="lg" variant="light" color="green">{availableSeats} {t`open`}</Badge>
                                </Group>
                                <Group gap="lg" className={classes.legend}>
                                    <span><i className={classes.occupiedSwatch}/>{t`Occupied`}</span>
                                    <span><i className={classes.openSwatch}/>{t`Open`}</span>
                                </Group>
                            </Group>
                        </Paper>

                        {seating.blueprint && (
                            <Group justify="flex-end" mt="md">
                                <SegmentedControl
                                    value={viewMode}
                                    onChange={value => setViewMode(value as 'blueprint' | 'tables')}
                                    data={[
                                        {label: t`Room blueprint`, value: 'blueprint'},
                                        {label: t`Table overview`, value: 'tables'},
                                    ]}
                                />
                            </Group>
                        )}

                        {seating.blueprint && viewMode === 'blueprint' ? (
                            <Paper className={classes.roomPlanner} withBorder radius="md">
                                <Group justify="space-between" mb="md">
                                    <Group gap="sm">
                                        <Button onClick={() => saveLayout()} loading={layoutMutation.isPending}>{t`Save table positions`}</Button>
                                        <Button variant="subtle" color="red" leftSection={<IconTrash size={16}/>} onClick={removeBlueprint} loading={blueprintBusy}>{t`Remove blueprint`}</Button>
                                    </Group>
                                    <Group className={classes.zoomControl} gap="xs">
                                        <IconZoomIn size={18}/><Slider min={60} max={180} value={zoom} onChange={setZoom}/><Text size="xs">{zoom}%</Text>
                                    </Group>
                                </Group>
                                <Text size="sm" c="dimmed" mb="sm">{t`Drag each table to its location, then save the table positions.`}</Text>
                                <div className={classes.roomViewport}>
                                    <div
                                        ref={canvasRef}
                                        className={classes.roomCanvas}
                                        style={{
                                            width: `${zoom}%`,
                                            aspectRatio: seating.blueprint.width && seating.blueprint.height
                                                ? `${seating.blueprint.width}/${seating.blueprint.height}`
                                                : '16/9',
                                            backgroundImage: `url(${seating.blueprint.url})`,
                                        }}
                                    >
                                        {positions.map(position => {
                                            const tableAssignments = assignmentsByTable.get(position.table_number) || new Map();
                                            const tableNumber = position.table_number;
                                            return (
                                                <div key={position.table_number} className={classes.roomTable} style={{left: `${position.x}%`, top: `${position.y}%`}}>
                                                    <button
                                                        type="button"
                                                        className={classes.roomTableTop}
                                                        onPointerDown={(event: ReactPointerEvent) => {
                                                            event.preventDefault();
                                                            setDraggingTable(position.table_number);
                                                        }}
                                                    >
                                                        {t`Table ${tableNumber}`}
                                                    </button>
                                                    {Array.from({length: seating.seats_per_table}, (_, index) => {
                                                        const seatNumber = index + 1;
                                                        const assignment = tableAssignments.get(seatNumber);
                                                        const angle = ((Math.PI * 2) / seating.seats_per_table) * index - Math.PI / 2;
                                                        return (
                                                            <Tooltip key={seatNumber} label={assignment ? `${assignment.first_name} ${assignment.last_name}` : t`Click to assign`}>
                                                                <button
                                                                    type="button"
                                                                    className={`${classes.roomSeat} ${assignment ? classes.occupied : classes.open}`}
                                                                    style={{left: `${50 + Math.cos(angle) * 64}%`, top: `${50 + Math.sin(angle) * 64}%`}}
                                                                    onPointerDown={event => event.stopPropagation()}
                                                                    onClick={() => setSelectedSeat({tableNumber: position.table_number, seatNumber, assignment})}
                                                                >{seatNumber}</button>
                                                            </Tooltip>
                                                        );
                                                    })}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            </Paper>
                        ) : (
                            <>
                                {!seating.blueprint && (
                                    <Alert icon={<IconPhoto/>} color="blue" mt="lg">{t`Upload a room blueprint to place the tables in their real locations. You can already assign attendees by clicking any green chair below.`}</Alert>
                                )}
                                <SimpleGrid className={classes.tableGrid} cols={{base: 1, sm: 2, lg: 3, xl: 4}}>
                                    {Array.from({length: seating.table_count}, (_, index) => {
                                        const tableNumber = index + 1;
                                        return <TableGraphic key={tableNumber} tableNumber={tableNumber} seatsPerTable={seating.seats_per_table} assignments={assignmentsByTable.get(tableNumber) || new Map()} onSelectSeat={setSelectedSeat}/>;
                                    })}
                                </SimpleGrid>
                            </>
                        )}
                    </>
                )}
            </PageBody>

            {configurationOpen && <EventSeatingModal isOpen onClose={configurationModal.close}/>}
            <Modal opened={!!selectedSeat} onClose={() => { setSelectedSeat(undefined); setAttendeeToAssign(null); }} title={selectedSeat ? t`Table ${selectedTableNumber}, seat ${selectedSeatNumber}` : ''}>
                {selectedSeat?.assignment ? (
                    <Stack>
                        <Text fw={600}>{selectedSeat.assignment.first_name} {selectedSeat.assignment.last_name}</Text>
                        <Text size="sm" c="dimmed">{selectedSeat.assignment.email}</Text>
                        <Button variant="light" onClick={() => { setSelectedAttendeeId(selectedSeat.assignment?.attendee_id); setSelectedSeat(undefined); }}>{t`View attendee`}</Button>
                        <Button color="red" variant="light" onClick={removeAssignment} loading={assignmentMutation.isPending}>{t`Remove seat assignment`}</Button>
                    </Stack>
                ) : (
                    <Stack>
                        <Select searchable clearable label={t`Attendee`} placeholder={t`Search by name or email`} data={attendeeOptions} value={attendeeToAssign} onChange={setAttendeeToAssign} nothingFoundMessage={t`No unseated attendees found`}/>
                        <Button onClick={assignSelectedSeat} disabled={!attendeeToAssign} loading={assignmentMutation.isPending}>{t`Assign attendee to this seat`}</Button>
                    </Stack>
                )}
            </Modal>
            {selectedAttendeeId && <ManageAttendeeModal isOpen attendeeId={selectedAttendeeId} onClose={() => setSelectedAttendeeId(undefined)}/>}
        </>
    );
};

export default TablesAndSeats;

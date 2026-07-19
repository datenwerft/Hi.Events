import {Document, Image, Page, StyleSheet, Text, View, pdf} from '@react-pdf/renderer';
import {EventSeatAssignment, EventSeatingBlueprint, EventTablePosition} from '../../../../types';

interface SeatingPlanPdfProps {
    assignments: EventSeatAssignment[];
    blueprint: EventSeatingBlueprint;
    positions: EventTablePosition[];
    seatsPerTable: number;
    tableLabel: string;
}

const PAGE_MAX_SIZE = 842;
const TABLE_SIZE = 74;
const TABLE_TOP_SIZE = 42;
const SEAT_SIZE = 17;

const styles = StyleSheet.create({
    page: {
        backgroundColor: '#ffffff',
        position: 'relative',
    },
    blueprint: {
        height: '100%',
        left: 0,
        objectFit: 'contain',
        position: 'absolute',
        top: 0,
        width: '100%',
    },
    table: {
        position: 'absolute',
    },
    tableTop: {
        alignItems: 'center',
        backgroundColor: '#ffffffeb',
        borderColor: '#868e96',
        borderRadius: 999,
        borderStyle: 'solid',
        borderWidth: 1.5,
        justifyContent: 'center',
        position: 'absolute',
    },
    tableLabel: {
        color: '#212529',
        fontSize: 7,
        fontWeight: 700,
    },
    seat: {
        alignItems: 'center',
        borderColor: '#ffffff',
        borderRadius: 999,
        borderStyle: 'solid',
        borderWidth: 1.2,
        color: '#ffffff',
        justifyContent: 'center',
        position: 'absolute',
    },
    seatLabel: {
        color: '#ffffff',
        fontSize: 6,
        fontWeight: 700,
    },
});

const getPageSize = (blueprint: EventSeatingBlueprint): [number, number] => {
    const ratio = blueprint.width && blueprint.height ? blueprint.width / blueprint.height : 16 / 9;
    return ratio >= 1
        ? [PAGE_MAX_SIZE, PAGE_MAX_SIZE / ratio]
        : [PAGE_MAX_SIZE * ratio, PAGE_MAX_SIZE];
};

// eslint-disable-next-line react-refresh/only-export-components
const SeatingPlanPdf = ({assignments, blueprint, positions, seatsPerTable, tableLabel}: SeatingPlanPdfProps) => {
    const [pageWidth, pageHeight] = getPageSize(blueprint);
    const assignedSeats = new Set(assignments.map(assignment => `${assignment.table_number}:${assignment.seat_number}`));

    return (
        <Document>
            <Page size={[pageWidth, pageHeight]} style={styles.page}>
                <Image src={blueprint.url} style={styles.blueprint}/>
                {positions.map(position => {
                    const scale = (position.size || 100) / 100;
                    const tableSize = TABLE_SIZE * scale;
                    const tableTopSize = TABLE_TOP_SIZE * scale;
                    const seatSize = SEAT_SIZE * scale;
                    const tableLeft = pageWidth * (position.x / 100) - tableSize / 2;
                    const tableTop = pageHeight * (position.y / 100) - tableSize / 2;

                    return (
                        <View
                            key={position.table_number}
                            style={[styles.table, {height: tableSize, left: tableLeft, top: tableTop, width: tableSize}]}
                        >
                            <View
                                style={[
                                    styles.tableTop,
                                    {
                                        height: tableTopSize,
                                        left: (tableSize - tableTopSize) / 2,
                                        top: (tableSize - tableTopSize) / 2,
                                        width: tableTopSize,
                                    },
                                ]}
                            >
                                <Text style={styles.tableLabel}>{tableLabel} {position.table_number}</Text>
                            </View>
                            {Array.from({length: seatsPerTable}, (_, index) => {
                                const seatNumber = index + 1;
                                const angle = ((Math.PI * 2) / seatsPerTable) * index - Math.PI / 2;
                                const centerX = tableSize / 2 + Math.cos(angle) * tableSize * 0.41;
                                const centerY = tableSize / 2 + Math.sin(angle) * tableSize * 0.41;
                                const isOccupied = assignedSeats.has(`${position.table_number}:${seatNumber}`);

                                return (
                                    <View
                                        key={seatNumber}
                                        style={[
                                            styles.seat,
                                            {
                                                backgroundColor: isOccupied ? '#e03131' : '#2f9e44',
                                                height: seatSize,
                                                left: centerX - seatSize / 2,
                                                top: centerY - seatSize / 2,
                                                width: seatSize,
                                            },
                                        ]}
                                    >
                                        <Text style={styles.seatLabel}>{seatNumber}</Text>
                                    </View>
                                );
                            })}
                        </View>
                    );
                })}
            </Page>
        </Document>
    );
};

export const createSeatingPlanPdf = async (props: SeatingPlanPdfProps): Promise<Blob> => pdf(
    <SeatingPlanPdf {...props}/>,
).toBlob();

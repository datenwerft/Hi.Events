import {EventTableShape} from '../../../../types';

export interface SeatPosition {
    x: number;
    y: number;
}

const getPerimeterPosition = (
    index: number,
    count: number,
    left: number,
    right: number,
    top: number,
    bottom: number,
): SeatPosition => {
    const width = right - left;
    const height = bottom - top;
    const perimeter = 2 * (width + height);
    let distance = (index / count) * perimeter;

    if (distance <= width / 2) return {x: 50 + distance, y: top};
    distance -= width / 2;
    if (distance <= height) return {x: right, y: top + distance};
    distance -= height;
    if (distance <= width) return {x: right - distance, y: bottom};
    distance -= width;
    if (distance <= height) return {x: left, y: bottom - distance};

    return {x: left + (distance - height), y: top};
};

export const getSeatPosition = (shape: EventTableShape, index: number, count: number): SeatPosition => {
    if (shape === 'round') {
        const angle = ((Math.PI * 2) / count) * index - Math.PI / 2;
        return {x: 50 + Math.cos(angle) * 43, y: 50 + Math.sin(angle) * 43};
    }

    return shape === 'rectangle'
        ? getPerimeterPosition(index, count, 4, 96, 22, 78)
        : getPerimeterPosition(index, count, 8, 92, 8, 92);
};

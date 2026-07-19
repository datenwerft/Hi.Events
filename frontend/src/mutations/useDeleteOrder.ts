import {useMutation, useQueryClient} from "@tanstack/react-query";
import {GenericPaginatedResponse, IdParam, Order} from "../types.ts";
import {orderClient} from "../api/order.client.ts";
import {GET_ORDER_QUERY_KEY} from "../queries/useGetOrder.ts";
import {GET_EVENT_ORDERS_QUERY_KEY} from "../queries/useGetEventOrders.ts";

export const useDeleteOrder = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({eventId, orderId}: { eventId: IdParam, orderId: IdParam }) =>
            orderClient.delete(eventId, orderId),

        onSuccess: (_, variables) => {
            queryClient.removeQueries({
                queryKey: [GET_ORDER_QUERY_KEY, variables.orderId],
            });

            queryClient.setQueriesData<GenericPaginatedResponse<Order>>({
                queryKey: [GET_EVENT_ORDERS_QUERY_KEY],
            }, (orders) => {
                if (!orders) {
                    return orders;
                }

                const remainingOrders = orders.data.filter(
                    (order) => String(order.id) !== String(variables.orderId),
                );

                if (remainingOrders.length === orders.data.length) {
                    return orders;
                }

                return {
                    ...orders,
                    data: remainingOrders,
                    meta: {
                        ...orders.meta,
                        total: Math.max(0, orders.meta.total - 1),
                    },
                };
            });

            return queryClient.invalidateQueries({
                queryKey: [GET_EVENT_ORDERS_QUERY_KEY],
            });
        },
    });
};

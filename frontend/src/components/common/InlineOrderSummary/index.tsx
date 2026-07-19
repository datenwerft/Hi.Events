import {useState} from "react";
import {Collapse} from "@mantine/core";
import {IconCalendarEvent, IconChevronDown} from "@tabler/icons-react";
import {t} from "@lingui/macro";
import classNames from "classnames";
import {Event, Order} from "../../../types.ts";
import {prettyDate} from "../../../utilites/dates.ts";
import {formatAnswer} from "../../../utilites/questionHelper.ts";
import classes from './InlineOrderSummary.module.scss';

interface InlineOrderSummaryProps {
    event: Event;
    order: Order;
    defaultExpanded?: boolean;
}

export const InlineOrderSummary = ({
    event,
    order,
    defaultExpanded = true,
}: InlineOrderSummaryProps) => {
    const [expanded, setExpanded] = useState(defaultExpanded);

    const coverImage = event?.images?.find((image) => image.type === 'EVENT_COVER');
    const location = event?.settings?.location_details?.city ||
        event?.settings?.location_details?.venue_name ||
        null;
    const registeredAttendees = order.attendees?.filter(
        (attendee) => attendee.first_name || attendee.last_name || attendee.email
    ) ?? [];

    return (
        <div className={classes.inlineOrderSummary}>
            <div
                className={classes.header}
                onClick={() => setExpanded(!expanded)}
                role="button"
                aria-expanded={expanded}
                tabIndex={0}
                onKeyDown={(e) => e.key === 'Enter' && setExpanded(!expanded)}
            >
                <span className={classes.headerTitle}>{t`Order Summary`}</span>
                <IconChevronDown
                    size={20}
                    className={classNames(classes.chevron, {
                        [classes.chevronRotated]: expanded
                    })}
                />
            </div>

            <Collapse in={expanded}>
                <div className={classes.content}>
                    <div className={classes.eventInfo}>
                        <div className={classes.eventImage}>
                            {coverImage ? (
                                <img src={coverImage.url} alt={event.title}/>
                            ) : (
                                <div className={classes.eventImagePlaceholder}>
                                    <IconCalendarEvent size={24}/>
                                </div>
                            )}
                        </div>
                        <div className={classes.eventDetails}>
                            <div className={classes.eventTitle}>{event.title}</div>
                            <div className={classes.eventMeta}>
                                {prettyDate(event.start_date, event.timezone, false)}
                            </div>
                            {location && (
                                <div className={classes.eventMeta}>{location}</div>
                            )}
                        </div>
                    </div>

                    <div className={classes.divider}/>

                    <div className={classes.lineItems}>
                        {order.order_items?.map((item) => (
                            <div key={item.id} className={classes.lineItem}>
                                <span title={item.item_name}
                                    className={classes.lineItemName}>{item.item_name}</span>
                                <span className={classes.lineItemQuantity}>
                                    {item.quantity} {item.quantity === 1 ? t`ticket` : t`tickets`}
                                </span>
                            </div>
                        ))}
                    </div>

                    {!!registeredAttendees.length && (
                        <>
                            <div className={classes.divider}/>
                            <div className={classes.attendees}>
                                <div className={classes.sectionTitle}>{t`Attendees`}</div>
                                {registeredAttendees.map((attendee) => (
                                    <div key={attendee.id ?? attendee.public_id} className={classes.attendee}>
                                        <dl className={classes.attendeeDetails}>
                                            <div>
                                                <dt>{t`First Name`}</dt>
                                                <dd>{attendee.first_name}</dd>
                                            </div>
                                            <div>
                                                <dt>{t`Last Name`}</dt>
                                                <dd>{attendee.last_name}</dd>
                                            </div>
                                            <div>
                                                <dt>{t`Email`}</dt>
                                                <dd>{attendee.email}</dd>
                                            </div>
                                            {attendee.question_answers?.map((questionAnswer) => (
                                                <div key={questionAnswer.question_answer_id ?? questionAnswer.question_id}>
                                                    <dt>{questionAnswer.title}</dt>
                                                    <dd>{formatAnswer(questionAnswer.answer)}</dd>
                                                </div>
                                            ))}
                                        </dl>
                                    </div>
                                ))}
                            </div>
                        </>
                    )}
                </div>
            </Collapse>
        </div>
    );
};

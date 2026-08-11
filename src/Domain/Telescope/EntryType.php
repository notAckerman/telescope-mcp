<?php

namespace AlexeyPenkov\TelescopeMcp\Domain\Telescope;

enum EntryType: string
{
    case Batch = 'batch';
    case Cache = 'cache';
    case ClientRequest = 'client_request';
    case Command = 'command';
    case Dump = 'dump';
    case Event = 'event';
    case Exception = 'exception';
    case Gate = 'gate';
    case Job = 'job';
    case Log = 'log';
    case Mail = 'mail';
    case Model = 'model';
    case Notification = 'notification';
    case Query = 'query';
    case Redis = 'redis';
    case Request = 'request';
    case ScheduledTask = 'schedule';
    case View = 'view';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}

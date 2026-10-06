local now = tonumber(ARGV[1])

local state = redis.call(
    'HMGET',
    KEYS[1],
    'timestamp',
    'sequence'
)

local lastTimestamp = tonumber(state[1])
local sequence = tonumber(state[2])

if not lastTimestamp then
    redis.call('HSET', KEYS[1],
        'timestamp', now,
        'sequence', 0
    )

    return { now, 0 }
end

if now < lastTimestamp then
    return { lastTimestamp, sequence }
end

if now > lastTimestamp then
    redis.call('HSET', KEYS[1],
        'timestamp', now,
        'sequence', 0
    )

    return { now, 0 }
end

sequence = sequence + 1

redis.call('HSET', KEYS[1], 'sequence', sequence)

return { now, sequence }

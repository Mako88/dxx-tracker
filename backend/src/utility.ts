import EventEmitter from "events";

// Per-key mutual exclusion. Each caller chains onto the previous holder's promise, so waiters run
// strictly one at a time; the key is dropped once its queue drains, so the map does not grow per client.
const locker = new Map<string, Promise<void>>();

export const withLock = async <T>(key: string, work: () => Promise<T>): Promise<T> => {
  const previous = locker.get(key) ?? Promise.resolve();
  let release: () => void = () => {};
  const current = new Promise<void>((resolve) => (release = resolve));
  const tail = previous.then(() => current);
  locker.set(key, tail);

  await previous;

  try {
    return await work();
  } finally {
    release();
    if (locker.get(key) === tail) {
      locker.delete(key);
    }
  }
};

export const eventEmitter: EventEmitter = new EventEmitter();

// Every open SSE stream adds a listener; the default cap of 10 would only print a false leak warning.
eventEmitter.setMaxListeners(0);

export const notifyGamesChanged = () => {
  eventEmitter.emit("gameCountChanged");
  eventEmitter.emit("gameListChanged");
};

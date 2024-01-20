import EventEmitter from "events";

interface KeyedLock {
  [key: string]: {
    promise: Promise<void>;
    resolver: () => void;
  };
}

const locker: KeyedLock = {};

export const lock = async (key: string): Promise<void> => {
  if (!locker[key]) {
    locker[key] = {
      promise: Promise.resolve(),
      resolver: () => {},
    };
  }

  await locker[key].promise;

  locker[key].promise = new Promise((resolve) => (locker[key].resolver = resolve));
};

export const unlock = (key: string): Promise<void> => {
  if (!locker[key]) {
    locker[key] = {
      promise: Promise.resolve(),
      resolver: () => {},
    };
    return;
  }

  locker[key].resolver();
};

export const liveGameIds: number[] = [];

export const eventEmitter: EventEmitter = new EventEmitter();

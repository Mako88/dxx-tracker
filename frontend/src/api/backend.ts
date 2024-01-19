import { GameFilter } from "../../../shared/enums";
import Game from "../../../shared/game";
import axios, { AxiosResponse } from "axios";

const host = import.meta.env.DEV ? "http://localhost" : "https://tracker.dxx-rebirth.com";
const port = "5050";

interface ErrorResponse {
  data: {
    error: unknown;
  };
  status: number;
}

export const getGameCount = async (live: boolean, filter: GameFilter): Promise<number> => {
  const response = await makeRequest(
    "/games/count",
    {
      live,
      filter,
    },
    "POST"
  );

  if (response.data.error) {
    return 0;
  }

  return response.data;
};

export const getGames = async (live: boolean, filter: GameFilter, page: number): Promise<Game[]> => {
  const response = await makeRequest(
    "/games",
    {
      live,
      filter,
      page,
    },
    "POST"
  );

  if (response.data.error) {
    return [];
  }

  return response.data;
};

export const heartbeat = async (): Promise<boolean> => {
  try {
    const response = await makeRequest("/heartbeat");
    return response.status === 200;
  } catch (err) {
    return false;
  }
};

const makeRequest = async (
  path: string,
  body: unknown = undefined,
  method: string = "GET"
): Promise<AxiosResponse | ErrorResponse> => {
  try {
    return await axios.request({
      url: `${host}:${port}${path}`,
      data: body,
      method,
    });
  } catch (err) {
    console.log(err);
    return {
      data: {
        error: err,
      },
      status: 500,
    };
  }
};

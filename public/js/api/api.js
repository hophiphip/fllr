import { host } from '../config.js';

export async function newGame(width, height) {
    try {
        const {data:response} = await axios({
            method: 'POST',
            url: host + '/api/game',
            withCredentials: false,
            headers: {
                'Accept' : 'application/json',
            },
            data: {
                'width': width,
                'height': height,
            }
        }).catch(function(error) {
            switch (error.response.status) {
                // Provided incorrect field size
                case 400: {
                    console.log(error.response);
                    break;
                }
            }
            throw error;
        });

        return response;
    }
    catch(error) {
        throw error;
    }
}

export async function getGame(id) {
    try {
        const {data:response} = await axios({
            method: 'GET',
            url: host + '/api/game/' + id,
            withCredentials: false,
            headers: {
                'Accept' : 'application/json',
            },
        }).catch(function(error) {
            switch (error.response.status) {
                // Incorrect request parameters
                case 400: {
                    console.log(error.response);
                    break;
                }

                // Incorrect game id
                case 404: {
                    console.log(error.response);
                    break;
                }
            }
            throw error;
        });

        return response;
    }
    catch(error) {
        throw error;
    }
}

export async function updateGame(game, color) {
    try {
        const {data:response} = await axios({
            method: 'PUT',
            url: host + '/api/game/' + game.id,
            withCredentials: false,
            headers: {
                'Accept' : 'application/json',
            },
            data: {
                'playerId' : game.currentPlayerId,
                'color' : color,
            }
        }).catch(function(error) {
            switch (error.response.status) {
                // Incorrect request parameters
                case 400: {
                    console.log(error.response);
                    break;
                }

                // Provided player can't make a move right now
                case 403: {
                    console.log(error.response);
                    break;
                }

                // Provided player can't choose this color right now
                case 409: {
                    console.log(error.response);
                    break;
                }

                // Incorrect game id
                case 404: {
                    console.log(error.response);
                    break;
                }
            }
            throw error;
        });

        return response;
    }
    catch(error) {
        throw error;
    }
}


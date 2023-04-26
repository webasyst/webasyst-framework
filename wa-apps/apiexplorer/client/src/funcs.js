export const appStaticUrl = (app_id = 'apiexplorer') => {
    return window.appState.rootUrl + 'wa-apps/' + app_id;
}

export const swaggerUrl = (app_id, version = 'v1') => {
    return appStaticUrl(app_id) + '/api/swagger/' + version + '.yaml';
}

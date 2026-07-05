export const appStaticUrl = (app_id = 'apiexplorer') => {
    return window.appState.rootUrl + 'wa-apps/' + app_id;
}

export const swaggerUrl = (app_id, version = 'v1') => {
    return window.appState.baseUrl + '?module=swaggerFile&app_id=' + app_id + '&version=' + version;
}

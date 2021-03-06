<template>
    <div class="container-fluid">
        <div class="row mb-3 mt-3">
            <div class="col-lg-12">
                <h1>App Management</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 sticky-column">
                <div class="card border-secondary mb-3" >
                    <h3 class="card-header">Apps</h3>
                    <div v-cloak v-if="player" class="list-group">
                        <a
                            v-for="playerApp in player.managerApps"
                            class="list-group-item list-group-item-action"
                            :class="{ active: app && app.id === playerApp.id }"
                            :href="'#AppManagement/' + playerApp.id">
                            {{ playerApp.name }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card border-secondary mb-3">
                    <h3 class="card-header">
                        Details
                    </h3>
                    <div v-cloak v-if="app" class="card-body">
                        <p>ID: {{ app.id }}</p>
                        <p>Name: {{ app.name }}</p>

                        <hr>

                        <h5>Application Secret</h5>
                        <p class="card-text">
                            Here you can generate a new application secret.
                            This will <em>invalidate</em> the existing secret.<br>
                            See also
                            <a v-cloak target="_blank" :href="settings.customization_github +
                                    '/blob/master/doc/documentation.md#authentication-of-applications'">
                                Authentication of applications</a>.
                        </p>
                        <p>
                            <button type="button" class="btn btn-warning" v-on:click="generateSecret()">
                                Generate new secret
                            </button>
                        </p>
                        <div v-cloak v-if="secret" class="alert alert-secondary mt-4">
                            <code>{{ secret }}</code>
                        </div>
                        <p v-cloak v-if="secret" class="card-text">
                            Please make a note of the new secret, it is not retrievable again!
                        </p>

                        <hr>

                        <h5>Groups</h5>
                        <table class="table table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>visibility</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="group in app.groups">
                                    <td>{{ group.id }}</td>
                                    <td>{{ group.name }}</td>
                                    <td>{{ group.visibility }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <h5>Roles</h5>
                        <table class="table table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="role in app.roles" v-if="role !== 'app'">
                                    <td>{{ role }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div> <!-- card -->
            </div> <!-- col -->
        </div> <!-- row  -->
    </div>
</template>

<script>
module.exports = {
    props: {
        route: Array,
        swagger: Object,
        player: [null, Object],
        settings: Object,
    },

    data: function() {
        return {
            app: null,
            secret: null,
        }
    },

    watch: {
        player: function() {
            this.setRoute();
        },

        route: function() {
            this.setRoute();
        }
    },

    methods: {
        setRoute: function() {
            this.secret = null;
            this.app = null;

            const appId = this.route[1] ? parseInt(this.route[1], 10) : null;
            if (appId && this.isManagerOf(appId)) {
                this.getApp(appId);
            }
        },

        /**
         * Check if player is manager of requested app.
         * (app-admins may see other apps, but cannot change the secret)
         *
         * @param appId
         * @returns {boolean}
         */
        isManagerOf(appId) {
            let isManager = false;
            for (let app of this.player.managerApps) {
                if (app.id === appId) {
                    isManager = true;
                }
            }
            return isManager;
        },

        getApp: function(id) {
            const vm = this;

            vm.loading(true);
            new this.swagger.AppApi().show(id, function(error, data) {
                vm.loading(false);
                if (error) { // 403 usually
                    return;
                }
                vm.app = data;
            });
        },

        generateSecret: function() {
            const vm = this;
            vm.loading(true);
            new this.swagger.AppApi().changeSecret(this.app.id, function(error, data) {
                vm.loading(false);
                if (error) { // 403 usually
                    return;
                }
                vm.secret = data;
            });
        },
    },
}
</script>

<style scoped>
    table {
        font-size: 90%;
    }
</style>

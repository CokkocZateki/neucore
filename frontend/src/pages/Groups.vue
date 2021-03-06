<template>
    <div class="container-fluid">

        <div v-cloak class="modal fade" id="leaveGroupModal">
            <div class="modal-dialog">
                <div v-cloak v-if="groupToLeave" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Leave Group</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Are you sure you want to leave this group?
                            You may lose access to some external services.
                        </p>
                        <p class="text-warning">{{ groupToLeave.name }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal" v-on:click="leave()">
                            LEAVE group
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3 mt-3">
            <div class="col-lg-12">
                <h1>Requestable Groups</h1>
                <table class="table table-hover table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="player && groups && applications"
                            v-for="group in groups"
                            :set="status = getStatus(group.id)">

                            <td>{{ group.name }}</td>
                            <td>{{ status }} <span v-if="status === 'accepted'">(but not a member)</span></td>
                            <td>
                                <button v-if="status === 'Member'"
                                        type="button" class="btn btn-warning btn-sm"
                                        v-on:click="askLeave(group.id, group.name)">
                                    Leave group
                                </button>
                                <button v-if="status === ''"
                                        type="button" class="btn btn-primary btn-sm"
                                        v-on:click="apply(group.id)">
                                    Apply
                                </button>
                                <button v-if="status === 'pending' || status === 'denied' || status === 'accepted'"
                                        type="button" class="btn btn-secondary btn-sm"
                                        v-on:click="cancel(group.id)">
                                    {{ status === 'pending' ? 'Cancel' : 'Remove' }} application
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
module.exports = {
    props: {
        initialized: Boolean,
        swagger: Object,
        player: [null, Object],
    },

    data: function() {
        return {
            groups: null,
            applications: null,
            groupToLeave: null,
        }
    },

    mounted: function() {
        if (this.initialized) { // on page change
            this.getPublicGroups();
            this.getApplications();
        }
    },

    watch: {
        initialized: function() { // on refresh
            this.getPublicGroups();
            this.getApplications();
        },
    },

    methods: {
        getPublicGroups: function() {
            const vm = this;
            vm.loading(true);
            new this.swagger.GroupApi().callPublic(function(error, data) {
                vm.loading(false);
                if (error) { // 403 usually
                    vm.groups = null;
                    return;
                }
                vm.groups = data;
            });
        },

        getApplications: function() {
            const vm = this;
            vm.loading(true);
            new this.swagger.PlayerApi().showApplications(function(error, data) {
                vm.loading(false);
                if (error) { // 403 usually
                    vm.applications = null;
                    return;
                }
                vm.applications = data;
            });
        },

        getStatus: function(groupId) {
            for (const member of this.player.groups) {
                if (member.id === groupId) {
                    return 'Member';
                }
            }
            for (const application of this.applications) {
                if (application.group.id === groupId) {
                    return application.status;
                }
            }
            return ''; // not a member, no application
        },

        apply: function(groupId) {
            const vm = this;
            vm.loading(true);
            new this.swagger.PlayerApi().addApplication(groupId, function() {
                vm.loading(false);
                vm.getApplications();
            });
        },

        cancel: function(groupId) {
            const vm = this;
            vm.loading(true);
            new this.swagger.PlayerApi().removeApplication(groupId, function() {
                vm.loading(false);
                vm.getApplications();
            });
        },

        askLeave: function(groupId, groupName) {
            this.groupToLeave = {
                id: groupId,
                name: groupName,
            };
            window.jQuery('#leaveGroupModal').modal('show');
        },

        leave: function() {
            const vm = this;
            vm.loading(true);
            new this.swagger.PlayerApi().leaveGroup(this.groupToLeave.id, function() {
                vm.loading(false);
                vm.$root.$emit('playerChange');
            });
            window.jQuery('#leaveGroupModal').modal('hide');
            this.groupToLeave = null;
        }
    }
}
</script>

<style scoped>
</style>

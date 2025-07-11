import { minimum } from '@/util'

export function registerMixin(component) {

    component.mixins = component.mixins || []

    component.mixins.push({
        data() {
            return {
                value: this.card.value,
                isLoading: false,
            }
        },
        mounted() {

            Nova.$on(`${ this.card.view }-updated`, (encoded, resource) => {

                const data = new FormData

                data.append(`${ this.card.view }_filter`, encoded)
                data.append('widget', this.card.key)
                data.append('view', this.card.view)

                const extraParam = resource ? `/${ resource }` : ''

                this.isLoading = true

                const request = minimum(Nova.request({
                    method: 'post',
                    url: `/nova-vendor/nova-dashboard/widget/update${ extraParam }`,
                    data,
                }))

                request
                    .then(response => this.value = response.data.value)
                    .finally(() => this.isLoading = false)

            })

        },
    })

}

export function registerDashboardMixin(component) {

    const originalDashboardEndpoint = `/nova-api/dashboards/${component.props.name.default}`

    component.computed.dashboardEndpoint = function () {
        return originalDashboardEndpoint + window.location.search
    }

}

import Card from './components/Card.vue'
import { registerMixin, registerDashboardMixin } from './components/Mixin'

Nova.booting(app => {

    const componentFn = app.component

    app.component = function (name, component) {

        if (name.endsWith('widget')) {
            registerMixin(component)
        }

        return componentFn.call(this, name, component)

    }

    app.component('nova-dashboard', Card)

})

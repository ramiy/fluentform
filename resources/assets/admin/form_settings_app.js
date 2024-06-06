import './helpers';

import Vue from 'vue';
import Router from 'vue-router';

import Errors from '../common/Errors';
import locale from 'element-ui/lib/locale';
import lang from 'element-ui/lib/locale/lang/en';
import notifier from './notifier';

import {
    Button,
    Row,
    Col,
    Radio,
    Checkbox,
    CheckboxGroup,
    RadioGroup,
    Select,
    Option,
    OptionGroup,
    Input,
    Form,
    FormItem,
    Tooltip,
    Switch,
    InputNumber,
    DatePicker,
    Table,
    TableColumn,
    Popover,
    Dropdown,
    DropdownMenu,
    DropdownItem,
    Collapse,
    CollapseItem,
    Slider,
    Tag,
    Loading,
    Message,
    Notification,
    Dialog,
    MessageBox,
    ButtonGroup,
    ColorPicker,
    Tabs,
    TabPane,
    Skeleton,
    SkeletonItem,
} from 'element-ui';

global.Errors = Errors;
global.ffSettingsEvents = new Vue();

// Set locale
locale.use(lang);
Vue.use(Router);

Vue.use(CollapseItem);
Vue.use(DropdownMenu);
Vue.use(DropdownItem);
Vue.use(Tag);
Vue.use(Slider);
Vue.use(ColorPicker);
Vue.use(RadioGroup);
Vue.use(Dropdown);
Vue.use(ButtonGroup);
Vue.use(Collapse);
Vue.use(Checkbox);
Vue.use(CheckboxGroup);
Vue.use(Popover);
Vue.use(Button);
Vue.use(Select);
Vue.use(Radio);
Vue.use(Table);
Vue.use(Row);
Vue.use(Col);
Vue.use(Form);
Vue.use(Input);
Vue.use(Option);
Vue.use(OptionGroup);
Vue.use(Switch);
Vue.use(Dialog);
Vue.use(Tooltip);
Vue.use(FormItem);
Vue.use(DatePicker);
Vue.use(TableColumn);
Vue.use(InputNumber);
Vue.use(Tabs);
Vue.use(TabPane);
Vue.use(Skeleton);
Vue.use(SkeletonItem);

Vue.use(Loading.directive);

Vue.prototype.$message = Message;
Vue.prototype.$notify = Notification;
Vue.prototype.$loading = Loading.service;
Vue.prototype.$confirm = MessageBox.confirm;

Vue.mixin({
    filters: {
        ucFirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        _startCase(string) {
            return _ff.startCase(string);
        }
    },
    methods: {
        $t(str) {
            let transString = window.FluentFormApp.form_settings_str[str];
            if(transString) {
                return transString;
            }
            return str;
        },

        ...notifier
    },
});

import Slack from './components/settings/Slack.vue';
import Zapier from './components/settings/Zapier.vue';
import LandingPages from './components/settings/LandingPage/index.vue';
import PostFeeds from './components/settings/PostFeeds.vue';
import BasicSettings from './components/settings/FormSettings.vue';
import Confirmations from './components/settings/Confirmations.vue';
import EmailNotifications from './components/settings/Notifications.vue';
import WebHook from './components/settings/WebHook/WebHook.vue';
import CustomCssJs from './components/settings/FormCustomCssJs.vue';
import GeneralIntegration from './components/settings/GeneralIntegration/Integration.vue';
import EditGeneralIntegration from './components/settings/GeneralIntegration/IntegrationEditor.vue';
import PdfFeeds from './components/settings/PdfFeeds.vue';
import PaymentSettings from './components/settings/PaymentSettings.vue';
import QuizSettings from './components/settings/QuizSettings.vue';
import CustomComponent from './components/CustomComponent.vue';

const routes = [
    {
        path: '*',
        name: 'formSettingsHome',
        component: BasicSettings
    },
    {
        path: '/payment-settings',
        name: 'payment_settings',
        component: PaymentSettings
    },
    {
        path: '/post-feeds',
        name: 'post_feeds',
        component: PostFeeds
    },
    {
        path: '/slack',
        name: 'slack',
        component: Slack
    },
    {
        path: '/email-settings',
        name: 'formEmailSettings',
        component: EmailNotifications
    },
    {
        path: '/pdf-feeds',
        name: 'PdfFeeds',
        component: PdfFeeds
    },
    {
        path: '/conditional-confirmations',
        name: 'formOtherConfirmations',
        component: Confirmations
    },
    {
        path: '/all-integrations',
        name: 'allIntegrations',
        component: GeneralIntegration
    },
    {
        path: '/all-integrations/:integration_id/:integration_name',
        name: 'edit_integration',
        component: EditGeneralIntegration
    },
    {
        path: '/custom-css-js',
        name: 'custom-css-js',
        component: CustomCssJs
    },
    {
        path: '/webhook',
        name: 'webhook',
        component: WebHook
    },
    {
        path: '/zapier',
        name: 'zapier',
        component: Zapier
    },
    {
        path: '/landing_pages',
        name: 'landing_pages',
        component: LandingPages
    },
    {
        path: '/quiz_settings',
        name: 'quiz_settings',
        component: QuizSettings
    },
    {
        path: '/custom-settings-component/:component_name',
        name: 'custom_settings',
        component: CustomComponent
    }
];

const router = new Router({
    routes: routes
});

import App from './components/settings/SettingsApp.vue';

const app = new Vue({
    el: '#ff_form_settings_app',
    render: h => h(App),
    router: router
});

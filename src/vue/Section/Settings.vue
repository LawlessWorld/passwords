<template>
    <div id="app-content">
        <div class="app-content-left settings">
            <section>
                <translate tag="h1" say="Security"/>
                <translate tag="h3" say="Password Generator"/>

                <translate tag="label" for="setting-security-level" say="Password strength"/>
                <select id="setting-security-level" v-model="settings['user.password.generator.strength']">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                </select>
                <span></span>

                <translate tag="label" for="setting-include-numbers" say="Include numbers"/>
                <input type="checkbox" id="setting-include-numbers" v-model="settings['user.password.generator.numbers']">
                <span></span>

                <translate tag="label" for="setting-include-special" say="Include special charaters"/>
                <input type="checkbox" id="setting-include-special" v-model="settings['user.password.generator.special']">
                <span></span>
            </section>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from "@vue/Components/Translate";

    export default {
        components: {
            Translate
        },
        data() {
            return {
                settings: {}
            };
        },
        created() {
            this.loadSettings();
        },
        methods   : {
            loadSettings() {
                this.settings = {};
                API.listSettings('user')
                   .then((d) => {this.settings = d;});
            },
            saveSettings() {
                API.setSettings(this.settings)
                   .catch(this.loadSettings);
            }
        },
        watch     : {
            settings: {
                handler(value, oldValue) {
                    if(value.hasOwnProperty('user.password.generator.strength') && oldValue.hasOwnProperty('user.password.generator.strength')) {
                        this.saveSettings();
                    }
                },
                deep: true
            }
        }
    };
</script>

<style lang="scss">
    .app-content-left.settings {
        padding : 10px;

        h1 {
            font-size   : 2.25em;
            font-weight : 200;
            margin      : 0.25em 0 1em;
        }

        h3 {
            margin-bottom : 0;
        }

        section {
            display               : grid;
            grid-template-columns : 3fr 2fr 1fr;
            max-width             : 400px;

            h1,
            h3 {
                grid-column-start : 1;
                grid-column-end   : 4;
            }

            label {
                line-height : 40px;
            }

            select {
                justify-self : end;
                width        : 100%;
            }

            input {
                justify-self : end;

                &[type=checkbox] {
                    cursor : pointer;
                }
            }
        }
    }
</style>
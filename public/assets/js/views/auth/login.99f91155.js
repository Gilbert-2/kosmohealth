import { f as a } from "../../auth-form.6e2f513b.js";
import { n as i } from "../../_plugin-vue2_normalizer.7b9a2fe2.js";
import "../../vendor/vendor-core.3574f8d9.js";
import "../../vendor/vendor-others.c8ba1624.js";
import "../../guest-footer.6b237a73.js";
import "../../AppLogo.91541531.js";

const r = {
  extends: a,
  data() {
    return {
      formData: {
        email: "",
        password: "",
      },
    };
  },
  methods: {
    submit() {
      this.isLoading = true;
      let o = this.$route.query;
      this.$gaEvent("engagement", "login", "Normal");
      this.Login(this.formData)
        .then((t) => {
          this.$toasted.success(t.message, this.$toastConfig);
          this.$gaEvent("activity", "loggedin", "Normal");
          if (this.configs.auth.twoFactorSecurity && this.twoFactorSet) {
            this.$router.push({ name: "authSecurity", query: o });
            this.isLoading = false;
          } else {
            let s = t.reload
              ? { name: "appDashboard", query: { reload: 1 } }
              : { name: "appDashboard" };
            if (o && o.ref && this.$router.resolve(o.ref)) {
              s = this.$router.resolve(o.ref).route;
            }
            if (this.hasRole("admin") && this.configs.system && this.configs.system.setupWizard) {
              s = { name: "setup" };
            }
            s = { ...s, query: { locale: void 0 } };
            this.ResetTwoFactorSet()
              .then(() => {
                this.$router.push(s);
              })
              .catch((e) => {
                this.isLoading = false;
                this.formErrors = formUtil.handleErrors(e);
              });
          }
        })
        .catch((t) => {
          this.isLoading = false;
          this.formErrors = formUtil.handleErrors(t);
        });
    },
    loginAs(o, t) {
      this.formData.email = o + "@" + t + ".com";
      this.formData.password = "password";
      this.submit();
    },
  },
  mounted() {
    this.SetCSRF();
    let o = this.$route.query && this.$route.query.cause ? this.$route.query.cause : null;
    if (o && o === "csrf_token_expired") {
      this.$toasted.error($t("general.csrf_token_expired"), this.$toastConfig.error);
      this.$router.push(this.withQuery({ name: this.$route.name, replace: true }));
    }
  },
};

var n = function () {
  var t = this,
    s = t._self._c;
  return s(
    "div",
    { staticClass: "guest-page", style: { backgroundColor: "white" } },
    [
      s(
        "box",
        [
          s("animated-loader", {
            attrs: {
              "is-loading": t.isLoading,
              "loader-color": t.vars.loaderColor,
            },
          }),
          s("guest-header", [s("h5", [t._v(t._s(t.$t("auth.login.page_title")))])]),
          s("section", { attrs: { role: "main" } }, [
            s(
              "form",
              {
                on: {
                  submit: function (e) {
                    e.preventDefault();
                    t.submit.apply(null, arguments);
                  },
                },
              },
              [
                s("base-input", {
                  staticClass: "mb-3",
                  attrs: {
                    "auto-focus": "",
                    label: t.$t("auth.login.props.email_username"),
                    type: "text",
                    "addon-left-icon": "fas fa-envelope",
                    error: t.formErrors.email,
                    autocomplete: "username",
                  },
                  on: {
                    "update:error": function (e) {
                      t.$set(t.formErrors, "email", e);
                    },
                  },
                  model: {
                    value: t.formData.email,
                    callback: function (e) {
                      t.$set(t.formData, "email", e);
                    },
                    expression: "formData.email",
                  },
                }),
                s("base-input", {
                  staticClass: "mb-3",
                  attrs: {
                    label: t.$t("auth.login.props.password"),
                    type: "password",
                    "addon-left-icon": "fas fa-key",
                    error: t.formErrors.password,
                    autocomplete: "current-password",
                  },
                  on: {
                    "update:error": function (e) {
                      t.$set(t.formErrors, "password", e);
                    },
                  },
                  model: {
                    value: t.formData.password,
                    callback: function (e) {
                      t.$set(t.formData, "password", e);
                    },
                    expression: "formData.password",
                  },
                }),
                s("div", { staticClass: "text-center mt-4" }, [
                  s(
                    "base-button",
                    {
                      staticClass: "my-4 text-lg",
                      attrs: { type: "submit", design: "primary", block: "" },
                    },
                    [t._v(t._s(t.$t("auth.login.login")))]
                  ),
                ], 1),
                t.configs && t.configs.auth
                  ? [
                      t.configs.auth.mobileOtpLogin ||
                      t.configs.auth.emailOtpLogin ||
                      t.configs.auth.socialLogin ||
                      t.configs.auth.facebook ||
                      t.configs.auth.twitter ||
                      t.configs.auth.github ||
                      t.configs.auth.google ||
                      t.showLocaleSwitcher
                        ? [
                            s("div", { staticClass: "row mt-4" }, [
                              t.showLocaleSwitcher
                                ? s("div", { staticClass: "col-3 text-left locale-switcher" }, [
                                    s(
                                      "base-dropdown",
                                      {
                                        attrs: {
                                          tag: "div",
                                          direction: "up",
                                          "menu-classes": "show-dropdown-up",
                                        },
                                      },
                                      [
                                        s(
                                          "base-button",
                                          {
                                            staticClass: "text-uppercase",
                                            attrs: {
                                              slot: "title",
                                              type: "button",
                                              design: "light",
                                              block: "",
                                              "data-toggle": "dropdown",
                                              role: "button",
                                              size: "sm",
                                            },
                                            slot: "title",
                                          },
                                          [
                                            s(
                                              "span",
                                              { staticClass: "nav-link-inner--text" },
                                              [t._v(t._s(t.selectedLocale))]
                                            ),
                                            s("i", {
                                              staticClass: "fas fa-chevron-up m-l-5",
                                            }),
                                          ]
                                        ),
                                        t._l(t.configs.system.locales, function (e) {
                                          return s(
                                            "button",
                                            {
                                              key: e.uuid,
                                              staticClass: "dropdown-item",
                                              attrs: { type: "button" },
                                              on: {
                                                click: function (u) {
                                                  return t.switchLocale(e);
                                                },
                                              },
                                            },
                                            [
                                              t._v(
                                                t._s(e.name) +
                                                  " (" + t._s(e.locale) + ")"
                                              ),
                                            ]
                                          );
                                        }),
                                      ],
                                      2
                                    ),
                                  ],
                                  1
                                )
                                : t._e(),
                              s(
                                "div",
                                {
                                  class: [
                                    t.showLocaleSwitcher
                                      ? "col-9"
                                      : "col-12",
                                    "text-right login-with-icons",
                                  ],
                                },
                                [
                                  t.configs.auth.emailOtpLogin
                                    ? s(
                                        "router-link",
                                        {
                                          directives: [
                                            {
                                              name: "b-tooltip",
                                              rawName: "v-b-tooltip.hover",
                                              modifiers: { hover: true },
                                            },
                                          ],
                                          staticClass: "btn btn-sm btn-gmail",
                                          attrs: {
                                            to: t.withQuery({
                                              name: "login-email-otp",
                                            }),
                                            title: t.$t(
                                              "auth.login.login_using_email_otp"
                                            ),
                                          },
                                        },
                                        [
                                          s("i", {
                                            staticClass: "fas fa-envelope-open-text",
                                          }),
                                        ]
                                      )
                                    : t._e(),
                                  t.configs.auth.socialLogin &&
                                  t.configs.auth.socialLoginProviders &&
                                  t.configs.auth.socialLoginProviders.length
                                    ? t._l(
                                        t.configs.auth.socialLoginProviders,
                                        function (e) {
                                          return s(
                                            "a",
                                            {
                                              directives: [
                                                {
                                                  name: "b-tooltip",
                                                  rawName: "v-b-tooltip.hover",
                                                  modifiers: { hover: true },
                                                },
                                              ],
                                              key: e,
                                              class: `btn btn-sm ${t.btns[e]}`,
                                              attrs: {
                                                href: `/auth/login/${e}`,
                                                title: t.$t(
                                                  "auth.login.login_with",
                                                  { attribute: e }
                                                ),
                                              },
                                            },
                                            [
                                              s("i", { class: t.icons[e] }),
                                            ]
                                          );
                                        }
                                      )
                                    : t._e(),
                                ],
                                2
                              ),
                            ]),
                            s("div", { staticClass: "text-center" }, [
                              s("hr", { staticClass: "my-4" }),
                            ]),
                          ]
                        : t._e(),
                      t.configs.auth.resetPassword ||
                      t.configs.auth.registration
                        ? s("div", { staticClass: "row mt-4" }, [
                            t.configs.auth.resetPassword
                              ? s(
                                  "div",
                                  {
                                    class: [
                                      "col-12 text-center",
                                      {
                                        "col-md-6 text-md-left":
                                          t.configs.auth.registration,
                                      },
                                    ],
                                  },
                                  [
                                    s(
                                      "router-link",
                                      {
                                        staticClass: "text-primary m-l-5",
                                        attrs: {
                                          to: t.withQuery({ name: "password" }),
                                        },
                                      },
                                      [
                                        s(
                                          "span",
                                          { staticClass: "font-weight-bold" },
                                          [
                                            t._v(
                                              t._s(
                                                t.$t(
                                                  "auth.password.forgot_password"
                                                )
                                              )
                                            ),
                                          ]
                                        ),
                                      ]
                                    ),
                                  ],
                                  1
                                )
                              : t._e(),
                            t.configs.auth.registration
                              ? s(
                                  "div",
                                  {
                                    class: [
                                      "col-12 text-center",
                                      {
                                        "col-md-6 text-md-right":
                                          t.configs.auth.resetPassword,
                                      },
                                    ],
                                  },
                                  [
                                    s(
                                      "router-link",
                                      {
                                        staticClass: "text-primary m-l-5",
                                        attrs: {
                                          to: t.withQuery({ name: "register" }),
                                        },
                                      },
                                      [
                                        s(
                                          "span",
                                          { staticClass: "font-weight-bold" },
                                          [
                                            t._v(
                                              t._s(
                                                t.$t(
                                                  "auth.register.register_here"
                                                )
                                              )
                                            ),
                                          ]
                                        ),
                                      ]
                                    ),
                                  ],
                                  1
                                )
                              : t._e(),
                          ])
                        : t._e(),
                    ]
                  : t._e(),
              ],
              2
            ),
          ]),
        ],
        1
      ),
      t.configs.system ? s : t._e(),
    ],
    1
  );
};

var l = [];

const c = i(r, n, l, false, null, "1fe1dc9a", null, null);
const _ = c.exports;
export { _ as default };

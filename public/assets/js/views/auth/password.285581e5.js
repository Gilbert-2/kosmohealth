import { f as r } from "../../auth-form.6e2f513b.js";
import { n as o } from "../../_plugin-vue2_normalizer.7b9a2fe2.js";
import "../../vendor/vendor-core.3574f8d9.js";
import "../../vendor/vendor-others.c8ba1624.js";
import "../../guest-footer.6b237a73.js";
import "../../AppLogo.91541531.js";

const i = {
  extends: r,
  data() {
    return {
      formData: {
        email: "",
        code: "",
        newPassword: "",
        newPasswordConfirmation: "",
      },
      requested: false,
      validated: false,
    };
  },
  methods: {
    submit() {
      this.isLoading = true;
      if (this.requested && this.validated) {
        this.ResetPassword(this.formData)
          .then((response) => {
            this.$toasted.success(response.message, this.$toastConfig);
            this.$router.push(this.withQuery({ name: "login" }));
          })
          .catch((error) => {
            this.isLoading = false;
            this.formErrors = formUtil.handleErrors(error);
          });
      } else if (this.requested) {
        this.ValidateResetPassword(this.formData)
          .then((response) => {
            this.$toasted.success(response.message, this.$toastConfig);
            this.validated = true;
            this.isLoading = false;
          })
          .catch((error) => {
            this.isLoading = false;
            this.formErrors = formUtil.handleErrors(error);
          });
      } else {
        this.RequestReset(this.formData)
          .then((response) => {
            this.$toasted.success(response.message, this.$toastConfig);
            this.requested = true;
            this.isLoading = false;
          })
          .catch((error) => {
            this.isLoading = false;
            this.formErrors = formUtil.handleErrors(error);
          });
      }
    },
  },
};

var n = function () {
  var s = this,
    t = s._self._c;
  return t("div", { staticClass: "guest-page", style: { backgroundColor: "white" } }, [
    t("box", [
      t("animated-loader", {
        attrs: {
          "is-loading": s.isLoading,
          "loader-color": s.vars.loaderColor,
        },
      }),
      t("guest-header", [t("h5", [s._v(s._s(s.$t("auth.password.forgot.page_title")))])]),
      t("section", { attrs: { role: "main" } }, [
        t(
          "form",
          {
            on: {
              submit: function (e) {
                return e.preventDefault(), s.submit.apply(null, arguments);
              },
            },
          },
          [
            t("base-input", {
              staticClass: "mb-3",
              attrs: {
                "auto-focus": !s.requested && !s.validated,
                disabled: s.requested || s.validated,
                label: s.$t("auth.password.props.email"),
                type: "text",
                "addon-left-icon": "fas fa-envelope",
                error: s.formErrors.email,
              },
              on: {
                "update:error": function (e) {
                  return s.$set(s.formErrors, "email", e);
                },
              },
              model: {
                value: s.formData.email,
                callback: function (e) {
                  s.$set(s.formData, "email", e);
                },
                expression: "formData.email",
              },
            }),
            s.requested
              ? [
                  s.validated
                    ? [
                        t("base-input", {
                          staticClass: "mb-3",
                          attrs: {
                            "auto-focus": s.requested && s.validated,
                            label: s.$t("auth.password.props.new_password"),
                            type: "password",
                            "addon-left-icon": "fas fa-key",
                            error: s.formErrors.newPassword,
                            autocomplete: "new-password",
                          },
                          on: {
                            "update:error": function (e) {
                              return s.$set(s.formErrors, "newPassword", e);
                            },
                          },
                          model: {
                            value: s.formData.newPassword,
                            callback: function (e) {
                              s.$set(s.formData, "newPassword", e);
                            },
                            expression: "formData.newPassword",
                          },
                        }),
                        t("base-input", {
                          staticClass: "mb-3",
                          attrs: {
                            label: s.$t("auth.password.props.new_password_confirmation"),
                            type: "password",
                            "addon-left-icon": "fas fa-key",
                            error: s.formErrors.newPasswordConfirmation,
                            autocomplete: "new-password",
                          },
                          on: {
                            "update:error": function (e) {
                              return s.$set(
                                s.formErrors,
                                "newPasswordConfirmation",
                                e
                              );
                            },
                          },
                          model: {
                            value: s.formData.newPasswordConfirmation,
                            callback: function (e) {
                              s.$set(
                                s.formData,
                                "newPasswordConfirmation",
                                e
                              );
                            },
                            expression: "formData.newPasswordConfirmation",
                          },
                        }),
                      ]
                    : t("base-input", {
                        staticClass: "mb-3",
                        attrs: {
                          "auto-focus": s.requested && !s.validated,
                          disabled: s.requested && s.validated,
                          label: s.$t("auth.password.props.code"),
                          type: "password",
                          "addon-left-icon": "fas fa-qrcode",
                          error: s.formErrors.code,
                        },
                        on: {
                          "update:error": function (e) {
                            return s.$set(s.formErrors, "code", e);
                          },
                        },
                        model: {
                          value: s.formData.code,
                          callback: function (e) {
                            s.$set(s.formData, "code", e);
                          },
                          expression: "formData.code",
                        },
                      }),
                ]
              : s._e(),
            t("div", { staticClass: "text-center" }, [
              s.requested && s.validated
                ? t(
                    "base-button",
                    {
                      staticClass: "my-4 text-lg",
                      attrs: { type: "submit", design: "primary", block: "" },
                    },
                    [s._v(s._s(s.$t("auth.password.reset_password")))]
                  )
                : s.requested
                ? t(
                    "base-button",
                    {
                      staticClass: "my-4 text-lg",
                      attrs: { type: "submit", design: "primary", block: "" },
                    },
                    [s._v(s._s(s.$t("auth.password.validate_code")))]
                  )
                : t(
                    "base-button",
                    {
                      staticClass: "my-4 text-lg",
                      attrs: { type: "submit", design: "primary", block: "" },
                    },
                    [s._v(s._s(s.$t("auth.password.request")))]
                  ),
            ], 1),
            t("div", { staticClass: "form-group m-b-0" }, [
              t("div", { staticClass: "col-sm-12 text-center" }, [
                t("p", [
                  t("i", { staticClass: "fas fa-chevron-left" }),
                  t(
                    "router-link",
                    {
                      staticClass: "text-primary m-l-5",
                      attrs: { to: s.withQuery({ name: "login" }) },
                    },
                    [
                      t("span", { staticClass: "font-weight-bold" }, [
                        s._v(s._s(s.$t("auth.login.back_to_login"))),
                      ]),
                    ]
                  ),
                ], 1),
              ]),
            ]),
          ],
          2
        ),
      ]),
    ], 1),
    s.configs.system
      ? t("guest-footer", {
          attrs: {
            "footer-credit": s.configs.system.footerCredit,
            version: s.configs.system.version,
          },
        })
      : s._e(),
  ], 1);
};

var d = [],
l = o(i, n, d, false, null, "b22fcf9c", null, null);
const w = l.exports;
export { w as default };

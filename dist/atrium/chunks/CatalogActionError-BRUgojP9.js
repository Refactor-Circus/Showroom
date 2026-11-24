import { AtriumNoAccess as e } from "@refactor-circus/atrium";
import { UAlert as t } from "@refactor-circus/atrium/ui";
import { createBlock as n, createCommentVNode as r, defineComponent as i, openBlock as a, unref as o } from "vue";
import { useI18n as s } from "vue-i18n";
// src/components/catalog/CatalogActionError.vue
var c = /* @__PURE__ */ i({
	__name: "CatalogActionError",
	props: { action: {} },
	setup(i) {
		let { t: c } = s();
		return (s, l) => {
			let u = e, d = t;
			return i.action.forbidden.value ? (a(), n(u, {
				key: 0,
				compact: "",
				title: o(c)("showroom.catalog.state.action_forbidden")
			}, null, 8, ["title"])) : i.action.message.value ? (a(), n(d, {
				key: 1,
				color: "error",
				variant: "subtle",
				title: i.action.message.value,
				"data-testid": "showroom-action-error"
			}, null, 8, ["title"])) : r("", !0);
		};
	}
});
export { c as t };

import { AtriumErrorCard as e, AtriumNoAccess as t } from "@refactor-circus/atrium";
import { UAlert as n, USkeleton as r } from "@refactor-circus/atrium/ui";
import { createBlock as i, defineComponent as a, openBlock as o, renderSlot as s, unref as c } from "vue";
import { useI18n as l } from "vue-i18n";
// src/components/catalog/CatalogState.vue
var u = /* @__PURE__ */ a({
	__name: "CatalogState",
	props: {
		ready: { type: Boolean },
		failed: { type: Boolean },
		status: {},
		compact: { type: Boolean }
	},
	setup(a) {
		let { t: u } = l();
		return (l, d) => {
			let f = t, p = n, m = e, h = r;
			return a.failed && a.status === 403 ? (o(), i(f, {
				key: 0,
				compact: a.compact
			}, null, 8, ["compact"])) : a.failed && a.status === 404 ? (o(), i(p, {
				key: 1,
				color: "neutral",
				variant: "subtle",
				icon: "i-lucide-search-x",
				title: c(u)("showroom.catalog.state.not_found"),
				"data-testid": "showroom-not-found"
			}, null, 8, ["title"])) : a.failed ? (o(), i(m, {
				key: 2,
				message: c(u)("showroom.catalog.state.load_failed")
			}, null, 8, ["message"])) : a.ready ? s(l.$slots, "default", {}, void 0, void 0, 3) : (o(), i(h, {
				key: 4,
				class: "h-32",
				"data-testid": "showroom-loading"
			}));
		};
	}
});
export { u as t };

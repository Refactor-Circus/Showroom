import { UButton as e } from "@refactor-circus/atrium/ui";
import { computed as t, createCommentVNode as n, createElementBlock as r, createTextVNode as i, createVNode as a, defineComponent as o, openBlock as s, toDisplayString as c, unref as l, withCtx as u } from "vue";
import { useI18n as d } from "vue-i18n";
import { useRoute as f, useRouter as p } from "vue-router";
// src/components/catalog/CatalogPager.vue?vue&type=script&setup=true&lang.ts
var m = {
	key: 0,
	class: "flex items-center gap-3 text-sm"
}, h = ["data-testid"], g = /* @__PURE__ */ o({
	__name: "CatalogPager",
	props: {
		testid: {},
		current: {},
		last: {},
		next: {},
		prev: {}
	},
	setup(o) {
		let g = o, { t: _ } = d(), v = f(), y = p(), b = t(() => g.current !== void 0 && g.last !== void 0), x = t(() => b.value ? (g.last ?? 1) > 1 : !!(g.next || g.prev));
		function S(e) {
			y.replace({ query: {
				...v.query,
				page: e > 1 ? String(e) : void 0
			} });
		}
		function C(e) {
			y.replace({ query: {
				...v.query,
				cursor: e || void 0
			} });
		}
		function w() {
			b.value ? S((g.current ?? 1) - 1) : C(g.prev);
		}
		function T() {
			b.value ? S((g.current ?? 1) + 1) : C(g.next);
		}
		return (t, d) => {
			let f = e;
			return l(x) ? (s(), r("div", m, [
				a(f, {
					variant: "outline",
					size: "sm",
					icon: "i-lucide-chevron-left",
					disabled: l(b) ? (g.current ?? 1) <= 1 : !g.prev,
					"data-testid": `${o.testid}-prev`,
					onClick: w
				}, {
					default: u(() => [i(c(l(_)("showroom.catalog.pager.previous")), 1)]),
					_: 1
				}, 8, ["disabled", "data-testid"]),
				l(b) ? (s(), r("span", {
					key: 0,
					class: "text-muted",
					"data-testid": `${o.testid}-page`
				}, c(l(_)("showroom.catalog.pager.page", {
					current: g.current,
					last: g.last
				})), 9, h)) : n("", !0),
				a(f, {
					variant: "outline",
					size: "sm",
					"trailing-icon": "i-lucide-chevron-right",
					disabled: l(b) ? (g.current ?? 1) >= (g.last ?? 1) : !g.next,
					"data-testid": `${o.testid}-next`,
					onClick: T
				}, {
					default: u(() => [i(c(l(_)("showroom.catalog.pager.next")), 1)]),
					_: 1
				}, 8, ["disabled", "data-testid"])
			])) : n("", !0);
		};
	}
});
export { g as t };

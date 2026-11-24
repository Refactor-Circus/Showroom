import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, f as a, k as o, r as s } from "./catalog-DCoO3A8a.js";
import { u as c, y as l } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as u, navigateTo as d } from "@refactor-circus/atrium";
import { UBadge as ee, UButton as te, UCard as ne, UFormField as re, UInput as ie } from "@refactor-circus/atrium/ui";
import { Fragment as ae, computed as f, createCommentVNode as p, createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as v, openBlock as y, reactive as oe, renderList as se, resolveComponent as ce, toDisplayString as b, unref as x, watch as S, withCtx as C, withModifiers as w } from "vue";
import { useI18n as T } from "vue-i18n";
import { useRoute as E } from "vue-router";
// src/pages/attribute-groups/[group].vue?vue&type=script&setup=true&lang.ts
var D = {
	class: "space-y-4",
	"data-testid": "showroom-attribute-group"
}, O = {
	key: 0,
	class: "space-y-5"
}, k = { class: "flex flex-wrap items-start justify-between gap-3" }, A = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-group-title"
}, j = { class: "font-mono text-sm text-muted" }, M = { class: "font-semibold" }, N = { class: "font-semibold" }, P = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-group-attributes-empty"
}, F = {
	key: 1,
	class: "w-full text-left text-sm"
}, I = { class: "text-muted" }, L = { class: "py-1 pr-3 font-medium" }, R = { class: "py-1 pr-3 font-medium" }, z = { class: "py-1 font-medium" }, B = { class: "divide-y divide-default" }, le = ["data-testid"], V = { class: "py-1 pr-3" }, H = { class: "py-1 pr-3" }, U = { class: "py-1" }, W = /* @__PURE__ */ v({
	__name: "[group]",
	setup(v) {
		let { t: W, locale: G } = T(), ue = E(), K = i(), q = f(() => String(ue.params.group)), { client: J, data: Y, pending: de, failed: fe, status: X, reload: pe } = o(async (e) => n(await e.GET("/attribute-groups/{group}", { params: { path: { group: q.value } } })).data, q), Z = f(() => Y.value), Q = f(() => Z.value?.attributes ?? []), $ = oe({
			label: "",
			sort_order: "0"
		});
		S(Y, () => {
			Z.value && ($.label = Z.value.labels[G.value] ?? "", $.sort_order = String(Z.value.sort_order));
		}, { immediate: !0 });
		async function me() {
			let e = Z.value;
			if (!J || !e) return;
			let t = {
				labels: c(e.labels, G.value, $.label),
				sort_order: l($.sort_order) ?? 0
			};
			await K.run(async () => n(await J.PATCH("/attribute-groups/{group}", {
				params: { path: { group: q.value } },
				body: r(t)
			}))) && await pe();
		}
		async function he() {
			J && window.confirm(W("showroom.structure.groups.confirm_delete")) && await K.run(async () => n(await J.DELETE("/attribute-groups/{group}", { params: { path: { group: q.value } } }))) && await d("/showroom/attribute-groups");
		}
		return (n, r) => {
			let i = te, o = ie, c = re, l = ne, d = ce("RouterLink"), f = ee, v = u;
			return y(), m("div", D, [_(v, {
				"screen-key": "showroom.attribute-group",
				record: x(q)
			}, {
				default: C(() => [_(t, {
					ready: !x(de) && x(Z) !== null,
					failed: x(fe),
					status: x(X)
				}, {
					default: C(() => [x(Z) ? (y(), m("div", O, [
						h("div", k, [h("div", null, [h("h1", A, b(x(a)(x(Z).labels, x(G), x(Z).code)), 1), h("p", j, b(x(Z).code), 1)]), _(i, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: x(K).busy.value,
							"data-testid": "showroom-group-delete",
							onClick: he
						}, {
							default: C(() => [g(b(x(W)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						_(e, { action: x(K) }, null, 8, ["action"]),
						_(l, null, {
							header: C(() => [h("h2", M, b(x(W)("showroom.structure.details")), 1)]),
							default: C(() => [h("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-group-form",
								onSubmit: w(me, ["prevent"])
							}, [
								_(c, {
									label: x(W)("showroom.catalog.fields.label_in", { locale: x(G) }),
									error: x(K).error("labels")
								}, {
									default: C(() => [_(o, {
										modelValue: x($).label,
										"onUpdate:modelValue": r[0] ||= (e) => x($).label = e,
										class: "w-64",
										"data-testid": "showroom-group-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								_(c, {
									label: x(W)("showroom.catalog.fields.sort_order"),
									error: x(K).error("sort_order")
								}, {
									default: C(() => [_(o, {
										modelValue: x($).sort_order,
										"onUpdate:modelValue": r[1] ||= (e) => x($).sort_order = e,
										type: "number",
										min: "0",
										class: "w-28",
										"data-testid": "showroom-group-sort"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								_(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: x(K).busy.value,
									"data-testid": "showroom-group-save"
								}, {
									default: C(() => [g(b(x(W)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)]),
							_: 1
						}),
						_(l, null, {
							header: C(() => [h("h2", N, b(x(W)("showroom.structure.groups.members")), 1)]),
							default: C(() => [x(Q).length === 0 ? (y(), m("p", P, b(x(W)("showroom.structure.groups.no_members")), 1)) : (y(), m("table", F, [h("thead", I, [h("tr", null, [
								h("th", L, b(x(W)("showroom.catalog.fields.code")), 1),
								h("th", R, b(x(W)("showroom.catalog.fields.label")), 1),
								h("th", z, b(x(W)("showroom.catalog.fields.type")), 1)
							])]), h("tbody", B, [(y(!0), m(ae, null, se(x(Q), (e) => (y(), m("tr", {
								key: e.code,
								"data-testid": `showroom-group-attribute-${e.code}`
							}, [
								h("td", V, [_(d, {
									to: x(s)(e.code),
									class: "font-mono hover:underline"
								}, {
									default: C(() => [g(b(e.code), 1)]),
									_: 2
								}, 1032, ["to"])]),
								h("td", H, b(x(a)(e.labels, x(G), e.code)), 1),
								h("td", U, [_(f, {
									color: "neutral",
									variant: "subtle"
								}, {
									default: C(() => [g(b(e.type), 1)]),
									_: 2
								}, 1024)])
							], 8, le))), 128))])]))]),
							_: 1
						})
					])) : p("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { W as default };

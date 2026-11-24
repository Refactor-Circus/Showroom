import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as ee } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, S as a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { t as c } from "./FlagFields-BuJVwniE.js";
import { b as l, d as u, g as d, r as f } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as p, AtriumPageHeader as m, AtriumState as te } from "@refactor-circus/atrium";
import { UBadge as ne, UButton as re, UCard as ie, UFormField as h, UInput as g, ULink as _, UTable as v } from "@refactor-circus/atrium/ui";
import { Fragment as ae, computed as y, createBlock as b, createElementBlock as x, createElementVNode as S, createTextVNode as C, createVNode as w, defineComponent as T, isRef as E, openBlock as D, reactive as O, ref as k, renderList as A, toDisplayString as j, unref as M, withCtx as N, withModifiers as P } from "vue";
import { useI18n as F } from "vue-i18n";
import { useRoute as I } from "vue-router";
// src/pages/flags/index.vue?vue&type=script&setup=true&lang.ts
var L = {
	class: "space-y-4",
	"data-testid": "showroom-flags"
}, R = { class: "space-y-4" }, z = { class: "font-semibold" }, B = { class: "flex flex-wrap items-end gap-3" }, oe = { class: "space-y-4" }, V = ["data-testid"], H = { class: "flex flex-wrap gap-1" }, U = { class: "tabular-nums" }, W = /* @__PURE__ */ T({
	__name: "index",
	setup(T) {
		let { t: W, locale: G } = F(), K = I(), q = i(), J = y(() => {
			let e = a(K.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: Y, data: X, pending: se, failed: ce, status: le, reload: ue } = s(async (e) => n(await e.GET("/flags", { params: { query: J.value } })), J), de = y(() => X.value?.data ?? []), Z = O({
			code: "",
			label: ""
		}), fe = y(() => [
			{
				accessorKey: "code",
				header: W("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: W("showroom.catalog.fields.label")
			},
			{
				accessorKey: "applies_to",
				header: W("showroom.structure.flags.applies_to")
			},
			{
				accessorKey: "sort_order",
				header: W("showroom.catalog.fields.sort_order"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		function Q() {
			return {
				applies_to: [...f],
				sort_order: ""
			};
		}
		let $ = k(Q());
		async function pe() {
			let e = {
				code: Z.code.trim(),
				...d(G.value, Z.label),
				applies_to: [...$.value.applies_to],
				...l($.value.sort_order)
			};
			Y && await q.run(async () => n(await Y.POST("/flags", { body: r(e) }))) && (Z.code = "", Z.label = "", $.value = Q(), await ue());
		}
		return (n, r) => {
			let i = m, a = g, s = h, l = re, d = ie, f = te, y = _, T = ne, O = v, k = p;
			return D(), x("div", L, [w(i, {
				title: M(W)("showroom.structure.flags.title"),
				description: M(W)("showroom.structure.flags.description")
			}, null, 8, ["title", "description"]), w(k, { "screen-key": "showroom.flags" }, {
				default: N(() => [S("div", R, [w(d, { "data-testid": "showroom-flag-new" }, {
					header: N(() => [S("h2", z, j(M(W)("showroom.structure.flags.new")), 1)]),
					default: N(() => [S("form", {
						class: "flex flex-col gap-4",
						"data-testid": "showroom-flag-create-form",
						onSubmit: P(pe, ["prevent"])
					}, [
						S("div", B, [w(s, {
							label: M(W)("showroom.catalog.fields.code"),
							hint: M(W)("showroom.structure.code_hint"),
							error: M(q).error("code"),
							required: ""
						}, {
							default: N(() => [w(a, {
								modelValue: M(Z).code,
								"onUpdate:modelValue": r[0] ||= (e) => M(Z).code = e,
								required: "",
								class: "w-56",
								"data-testid": "showroom-flag-create-code"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]), w(s, {
							label: M(W)("showroom.catalog.fields.label_in", { locale: M(G) }),
							error: M(q).error("labels")
						}, {
							default: N(() => [w(a, {
								modelValue: M(Z).label,
								"onUpdate:modelValue": r[1] ||= (e) => M(Z).label = e,
								class: "w-56",
								"data-testid": "showroom-flag-create-label"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])]),
						w(c, {
							modelValue: M($),
							"onUpdate:modelValue": r[2] ||= (e) => E($) ? $.value = e : null,
							action: M(q),
							testid: "showroom-flag-create"
						}, null, 8, ["modelValue", "action"]),
						S("div", null, [w(l, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: M(q).busy.value,
							"data-testid": "showroom-flag-create-submit"
						}, {
							default: N(() => [C(j(M(W)("showroom.catalog.create")), 1)]),
							_: 1
						}, 8, ["loading"])])
					], 32), w(e, {
						action: M(q),
						class: "mt-3"
					}, null, 8, ["action"])]),
					_: 1
				}), w(ee, {
					ready: !M(se) && M(X) !== null,
					failed: M(ce),
					status: M(le)
				}, {
					default: N(() => [S("div", oe, [w(O, {
						data: M(de),
						columns: M(fe),
						"data-testid": "showroom-flags-table"
					}, {
						empty: N(() => [w(f, {
							compact: "",
							title: M(W)("showroom.structure.flags.empty"),
							"data-testid": "showroom-flags-empty"
						}, null, 8, ["title"])]),
						"code-cell": N(({ row: { original: e } }) => [S("span", { "data-testid": `showroom-flag-row-${e.code}` }, [w(y, {
							to: M(u)(e.code),
							class: "font-mono font-medium hover:underline"
						}, {
							default: N(() => [C(j(e.code), 1)]),
							_: 2
						}, 1032, ["to"])], 8, V)]),
						"label-cell": N(({ row: { original: e } }) => [C(j(M(o)(e.labels, M(G), e.code)), 1)]),
						"applies_to-cell": N(({ row: { original: e } }) => [S("span", H, [(D(!0), x(ae, null, A(e.applies_to, (e) => (D(), b(T, {
							key: e,
							color: "neutral",
							variant: "outline"
						}, {
							default: N(() => [C(j(M(W)(`showroom.structure.flags.subject_${e}`)), 1)]),
							_: 2
						}, 1024))), 128))])]),
						"sort_order-cell": N(({ row: { original: e } }) => [S("span", U, j(e.sort_order), 1)]),
						_: 1
					}, 8, ["data", "columns"]), w(t, {
						next: M(X)?.meta?.next_cursor ?? null,
						prev: M(X)?.meta?.prev_cursor ?? null,
						testid: "showroom-flags"
					}, null, 8, ["next", "prev"])])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])])]),
				_: 1
			})]);
		};
	}
});
export { W as default };

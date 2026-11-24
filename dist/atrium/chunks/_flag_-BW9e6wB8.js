import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, f as ee, k as a } from "./catalog-DCoO3A8a.js";
import { t as o } from "./FlagFields-CeNVNtmX.js";
import { u as s, y as c } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as l, navigateTo as u } from "@refactor-circus/atrium";
import { UButton as d, UCard as f, UFormField as p, UInput as m } from "@refactor-circus/atrium/ui";
import { computed as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, isRef as S, openBlock as C, ref as w, toDisplayString as T, unref as E, watch as D, withCtx as O, withModifiers as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRoute as j } from "vue-router";
// src/pages/flags/[flag].vue?vue&type=script&setup=true&lang.ts
var M = {
	class: "space-y-4",
	"data-testid": "showroom-flag"
}, N = {
	key: 0,
	class: "space-y-5"
}, P = { class: "flex flex-wrap items-start justify-between gap-3" }, F = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-flag-title"
}, I = { class: "text-sm text-muted" }, L = {
	class: "font-mono",
	"data-testid": "showroom-flag-code"
}, R = { class: "font-semibold" }, z = { class: "text-sm text-muted" }, B = /* @__PURE__ */ x({
	__name: "[flag]",
	setup(x) {
		let { t: B, locale: V } = A(), H = j(), U = i(), W = h(() => String(H.params.flag)), { client: G, data: K, pending: q, failed: J, status: Y, reload: X } = a(async (e) => n(await e.GET("/flags/{flag}", { params: { path: { flag: W.value } } })).data, W), Z = h(() => K.value), Q = w(""), $ = w({
			applies_to: [],
			sort_order: "0"
		});
		D(K, () => {
			Z.value && (Q.value = Z.value.labels[V.value] ?? "", $.value = {
				applies_to: [...Z.value.applies_to],
				sort_order: String(Z.value.sort_order)
			});
		}, { immediate: !0 });
		async function te() {
			let e = Z.value;
			if (!G || !e) return;
			let t = {
				labels: s(e.labels, V.value, Q.value),
				applies_to: [...$.value.applies_to],
				sort_order: c($.value.sort_order) ?? 0
			};
			await U.run(async () => n(await G.PATCH("/flags/{flag}", {
				params: { path: { flag: W.value } },
				body: r(t)
			}))) && await X();
		}
		async function ne() {
			G && window.confirm(B("showroom.structure.flags.confirm_delete")) && await U.run(async () => n(await G.DELETE("/flags/{flag}", { params: { path: { flag: W.value } } }))) && await u("/showroom/flags");
		}
		return (n, r) => {
			let i = d, a = m, s = p, c = f, u = l;
			return C(), _("div", M, [b(u, {
				"screen-key": "showroom.flag",
				record: E(W)
			}, {
				default: O(() => [b(t, {
					ready: !E(q) && E(Z) !== null,
					failed: E(J),
					status: E(Y)
				}, {
					default: O(() => [E(Z) ? (C(), _("div", N, [
						v("div", P, [v("div", null, [v("h1", F, T(E(ee)(E(Z).labels, E(V), E(Z).code)), 1), v("p", I, [y(T(E(B)("showroom.catalog.fields.code")) + ": ", 1), v("span", L, T(E(Z).code), 1)])]), b(i, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: E(U).busy.value,
							"data-testid": "showroom-flag-delete",
							onClick: ne
						}, {
							default: O(() => [y(T(E(B)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						b(e, { action: E(U) }, null, 8, ["action"]),
						b(c, null, {
							header: O(() => [v("h2", R, T(E(B)("showroom.structure.details")), 1)]),
							default: O(() => [v("form", {
								class: "flex flex-col gap-4",
								"data-testid": "showroom-flag-form",
								onSubmit: k(te, ["prevent"])
							}, [
								b(s, {
									label: E(B)("showroom.catalog.fields.label_in", { locale: E(V) }),
									error: E(U).error("labels")
								}, {
									default: O(() => [b(a, {
										modelValue: E(Q),
										"onUpdate:modelValue": r[0] ||= (e) => S(Q) ? Q.value = e : null,
										class: "w-64",
										"data-testid": "showroom-flag-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								b(o, {
									modelValue: E($),
									"onUpdate:modelValue": r[1] ||= (e) => S($) ? $.value = e : null,
									action: E(U),
									testid: "showroom-flag"
								}, null, 8, ["modelValue", "action"]),
								v("div", null, [b(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: E(U).busy.value,
									"data-testid": "showroom-flag-save"
								}, {
									default: O(() => [y(T(E(B)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						}),
						v("p", z, T(E(B)("showroom.structure.flags.delete_note")), 1)
					])) : g("", !0)]),
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
export { B as default };

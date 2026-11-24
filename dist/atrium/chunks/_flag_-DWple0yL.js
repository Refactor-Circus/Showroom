import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, f as a, k as o } from "./catalog-DCoO3A8a.js";
import { t as s } from "./FlagFields-BuJVwniE.js";
import { u as c, y as l } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as u, AtriumPageHeader as d, navigateTo as f } from "@refactor-circus/atrium";
import { UButton as p, UCard as m, UFormField as h, UInput as g } from "@refactor-circus/atrium/ui";
import { computed as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, isRef as w, openBlock as T, ref as E, toDisplayString as D, unref as O, watch as k, withCtx as A, withModifiers as j } from "vue";
import { useI18n as M } from "vue-i18n";
import { useRoute as N } from "vue-router";
// src/pages/flags/[flag].vue?vue&type=script&setup=true&lang.ts
var P = {
	class: "space-y-4",
	"data-testid": "showroom-flag"
}, F = {
	key: 0,
	class: "space-y-5"
}, I = {
	class: "font-mono",
	"data-testid": "showroom-flag-code"
}, L = { class: "font-semibold" }, R = { class: "text-sm text-muted" }, z = /* @__PURE__ */ C({
	__name: "[flag]",
	setup(C) {
		let { t: z, locale: B } = M(), V = N(), H = i(), U = _(() => String(V.params.flag)), { client: W, data: G, pending: K, failed: q, status: J, reload: Y } = o(async (e) => n(await e.GET("/flags/{flag}", { params: { path: { flag: U.value } } })).data, U), X = _(() => G.value), Z = E(""), Q = E({
			applies_to: [],
			sort_order: "0"
		});
		k(G, () => {
			X.value && (Z.value = X.value.labels[B.value] ?? "", Q.value = {
				applies_to: [...X.value.applies_to],
				sort_order: String(X.value.sort_order)
			});
		}, { immediate: !0 });
		async function $() {
			let e = X.value;
			if (!W || !e) return;
			let t = {
				labels: c(e.labels, B.value, Z.value),
				applies_to: [...Q.value.applies_to],
				sort_order: l(Q.value.sort_order) ?? 0
			};
			await H.run(async () => n(await W.PATCH("/flags/{flag}", {
				params: { path: { flag: U.value } },
				body: r(t)
			}))) && await Y();
		}
		async function ee() {
			W && window.confirm(z("showroom.structure.flags.confirm_delete")) && await H.run(async () => n(await W.DELETE("/flags/{flag}", { params: { path: { flag: U.value } } }))) && await f("/showroom/flags");
		}
		return (n, r) => {
			let i = p, o = d, c = g, l = h, f = m, _ = u;
			return T(), y("div", P, [S(_, {
				"screen-key": "showroom.flag",
				record: O(U)
			}, {
				default: A(() => [S(t, {
					ready: !O(K) && O(X) !== null,
					failed: O(q),
					status: O(J)
				}, {
					default: A(() => [O(X) ? (T(), y("div", F, [
						S(o, {
							title: O(a)(O(X).labels, O(B), O(X).code),
							"data-testid": "showroom-flag-title"
						}, {
							meta: A(() => [b("p", null, [x(D(O(z)("showroom.catalog.fields.code")) + ": ", 1), b("span", I, D(O(X).code), 1)])]),
							default: A(() => [S(i, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: O(H).busy.value,
								"data-testid": "showroom-flag-delete",
								onClick: ee
							}, {
								default: A(() => [x(D(O(z)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["title"]),
						S(e, { action: O(H) }, null, 8, ["action"]),
						S(f, null, {
							header: A(() => [b("h2", L, D(O(z)("showroom.structure.details")), 1)]),
							default: A(() => [b("form", {
								class: "flex flex-col gap-4",
								"data-testid": "showroom-flag-form",
								onSubmit: j($, ["prevent"])
							}, [
								S(l, {
									label: O(z)("showroom.catalog.fields.label_in", { locale: O(B) }),
									error: O(H).error("labels")
								}, {
									default: A(() => [S(c, {
										modelValue: O(Z),
										"onUpdate:modelValue": r[0] ||= (e) => w(Z) ? Z.value = e : null,
										class: "w-64",
										"data-testid": "showroom-flag-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								S(s, {
									modelValue: O(Q),
									"onUpdate:modelValue": r[1] ||= (e) => w(Q) ? Q.value = e : null,
									action: O(H),
									testid: "showroom-flag"
								}, null, 8, ["modelValue", "action"]),
								b("div", null, [S(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: O(H).busy.value,
									"data-testid": "showroom-flag-save"
								}, {
									default: A(() => [x(D(O(z)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						}),
						b("p", R, D(O(z)("showroom.structure.flags.delete_note")), 1)
					])) : v("", !0)]),
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
export { z as default };
